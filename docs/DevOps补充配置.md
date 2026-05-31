# 怡安印刷商城 — DevOps补充配置（DevOps Supplement）

> **版本**: v1.0  
> **日期**: 2026-05-30  
> **用途**: 补充运维与部署手册中缺失的KMS操作SOP、蓝绿部署脚本、CI修复  
> **技术栈**: Docker 24.x + Docker Compose 2.x + Linux Ubuntu 22.04 LTS + GitLab CI/CD

---

## 目录

1. [KMS操作SOP](#1-kms操作sop)
2. [蓝绿部署脚本](#2-蓝绿部署脚本)
3. [CI/CD修复](#3-cicd修复)
4. [域名/SSL证书操作](#4-域名ssl证书操作)
5. [Nginx完整配置](#5-nginx完整配置)

---

## 1. KMS操作SOP

### 1.1 阿里云KMS操作

```bash
# ========== 1. 安装阿里云CLI ==========
curl -O https://aliyuncli.alicdn.com/aliyun-cli-linux-latest-amd64.tgz
tar -xzf aliyun-cli-linux-latest-amd64.tgz
sudo mv aliyun /usr/local/bin/

# 配置凭证
aliyun configure
# AccessKey ID: <your-ak-id>
# AccessKey Secret: <your-ak-secret>
# Default Region: cn-hangzhou
# Default Output Format: json

# ========== 2. 创建KMS密钥 ==========
aliyun kms CreateKey \
  --Description "怡安印刷商城生产环境密钥" \
  --KeySpec Aliyun_AES_256 \
  --KeyUsage ENCRYPT/DECRYPT \
  --ProtectionLevel SOFTWARE

# 返回 KeyId: key-hzz6******

# ========== 3. 创建密钥别名（便于管理） ==========
aliyun kms CreateAlias \
  --KeyId key-hzz6****** \
  --AliasName alias/yian-shop-prod

# ========== 4. 加密敏感配置 ==========
# 加密数据库密码
aliyun kms Encrypt \
  --KeyId alias/yian-shop-prod \
  --Plaintext $(echo -n 'YourStrongDBPassword123!' | base64) \
  --EncryptionContext '{"env":"prod","service":"mysql"}'

# 返回 CipherTextBlob: <加密后的密文>

# ========== 5. 在.env中存储密文（而非明文） ==========
# .env.production
DB_PASSWORD_KMS=CiC4x******  # KMS加密后的密文
DB_PASSWORD_KMS_CONTEXT=env=prod,service=mysql

# ========== 6. Laravel中解密使用 ==========
# config/database.php
'password' => decryptKms(env('DB_PASSWORD_KMS'), env('DB_PASSWORD_KMS_CONTEXT')),

# app/Helpers/KmsHelper.php
function decryptKms(string $cipherText, string $context): string
{
    if (empty($cipherText)) {
        return env('DB_PASSWORD', ''); // 本地开发回退到明文
    }

    $client = new \AlibabaCloud\KMS\Kms([
        'regionId' => env('KMS_REGION', 'cn-hangzhou'),
        'accessKeyId' => env('KMS_ACCESS_KEY_ID'),
        'accessKeySecret' => env('KMS_ACCESS_KEY_SECRET'),
    ]);

    $contextPairs = [];
    foreach (explode(',', $context) as $pair) {
        [$k, $v] = explode('=', $pair);
        $contextPairs[$k] = $v;
    }

    $response = $client->v2()->decrypt([
        'CiphertextBlob' => $cipherText,
        'EncryptionContext' => $contextPairs,
    ]);

    return base64_decode($response['Plaintext']);
}

# ========== 7. 密钥轮换（每90天执行） ==========
aliyun kms UpdateRotationPolicy \
  --KeyId alias/yian-shop-prod \
  --EnableAutomaticRotation true \
  --RotationInterval 7776000  # 90天（秒）

# ========== 8. Docker Compose中注入KMS凭证 ==========
# docker-compose.yml (production)
services:
  app:
    environment:
      - KMS_ACCESS_KEY_ID=${KMS_ACCESS_KEY_ID}
      - KMS_ACCESS_KEY_SECRET=${KMS_ACCESS_KEY_SECRET}
      - KMS_REGION=${KMS_REGION:-cn-hangzhou}
    secrets:
      - kms_access_key_id
      - kms_access_key_secret

secrets:
  kms_access_key_id:
    file: ./secrets/kms_access_key_id.txt
  kms_access_key_secret:
    file: ./secrets/kms_access_key_secret.txt

# .gitignore 必须包含:
# secrets/
# *.pem
# .env.*
```

### 1.2 .env.example 补充KMS变量

```bash
# ============================================================
# KMS敏感配置（生产环境必填）
# ============================================================
KMS_ACCESS_KEY_ID=
KMS_ACCESS_KEY_SECRET=
KMS_REGION=cn-hangzhou
KMS_KEY_ID=alias/yian-shop-prod

# 加密后的敏感配置（使用KMS加密后填入）
DB_PASSWORD_KMS=
DB_PASSWORD_KMS_CONTEXT=env=prod,service=mysql
REDIS_PASSWORD_KMS=
REDIS_PASSWORD_KMS_CONTEXT=env=prod,service=redis
WECHAT_PAY_APIV3_KEY_KMS=
WECHAT_PAY_APIV3_KEY_KMS_CONTEXT=env=prod,service=payment
ALIPAY_APP_PRIVATE_KEY_KMS=
ALIPAY_APP_PRIVATE_KEY_KMS_CONTEXT=env=prod,service=payment
```

---

## 2. 蓝绿部署脚本

### 2.1 完整蓝绿部署脚本

```bash
#!/bin/bash
# scripts/blue-green-deploy.sh
# 蓝绿部署脚本 - 零停机上线

set -euo pipefail

APP_NAME="yian-shop"
DOCKER_COMPOSE_FILE="docker-compose.prod.yml"
BLUE_ENV="${APP_NAME}-blue"
GREEN_ENV="${APP_NAME}-green"
NGINX_CONF="/etc/nginx/conf.d/${APP_NAME}.conf"
HEALTH_CHECK_URL="http://localhost:__PORT__/api/v1/health"
HEALTH_CHECK_TIMEOUT=60

# 颜色输出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"; }
warn() { echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"; }
error() { echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"; exit 1; }

# 1. 确定当前活跃环境（蓝或绿）
get_active_env() {
    local upstream
    upstream=$(grep -oP "proxy_pass http://\K(${BLUE_ENV}|${GREEN_ENV})" "$NGINX_CONF" 2>/dev/null || echo "$BLUE_ENV")
    echo "$upstream"
}

# 2. 确定目标环境
get_target_env() {
    local active=$1
    if [ "$active" = "$BLUE_ENV" ]; then
        echo "$GREEN_ENV"
    else
        echo "$BLUE_ENV"
    fi
}

# 3. 部署目标环境
deploy_target() {
    local target=$1
    local port=$2

    log "开始部署 $target (port: $port)"

    # 3.1 拉取最新代码
    cd /var/www/${APP_NAME} || error "项目目录不存在"
    git fetch origin
    git reset --hard origin/main

    # 3.2 备份数据库
    local backup_file="/backup/db_${APP_NAME}_$(date +%Y%m%d_%H%M%S).sql"
    mysqldump -u root -p"${DB_PASSWORD}" ${APP_NAME} > "$backup_file" || warn "数据库备份失败"
    log "数据库已备份到 $backup_file"

    # 3.3 构建Docker镜像
    export APP_PORT=$port
    export COMPOSE_PROJECT_NAME=$target
    docker compose -f $DOCKER_COMPOSE_FILE build app

    # 3.4 启动目标环境
    docker compose -f $DOCKER_COMPOSE_FILE -p $target up -d

    # 3.5 执行数据库Migration
    docker compose -f $DOCKER_COMPOSE_FILE -p $target exec -T app \
        php artisan migrate --force || error "Migration失败"

    # 3.6 缓存优化
    docker compose -f $DOCKER_COMPOSE_FILE -p $target exec -T app \
        bash -c "php artisan config:cache && php artisan route:cache && php artisan view:cache"

    # 3.7 重启Octane
    docker compose -f $DOCKER_COMPOSE_FILE -p $target exec -T app \
        php artisan octane:reload || docker compose -f $DOCKER_COMPOSE_FILE -p $target restart app

    log "$target 部署完成"
}

# 4. 健康检查
health_check() {
    local target=$1
    local port=$2
    local url="${HEALTH_CHECK_URL/__PORT__/$port}"
    local elapsed=0

    log "开始健康检查: $url"

    while [ $elapsed -lt $HEALTH_CHECK_TIMEOUT ]; do
        if curl -sf "$url" > /dev/null 2>&1; then
            log "健康检查通过 ($elapsed秒)"
            return 0
        fi
        sleep 2
        elapsed=$((elapsed + 2))
        echo -n "."
    done

    error "健康检查超时 (${HEALTH_CHECK_TIMEOUT}秒)"
}

# 5. 切换流量
switch_traffic() {
    local target=$1
    local port=$2

    log "切换流量到 $target (port: $port)"

    # 更新Nginx upstream
    sed -i "s/proxy_pass http:\/\/[^;]*/proxy_pass http:\/\/$target/" "$NGINX_CONF"
    sed -i "s/server [^:]*:[0-9]*/server 127.0.0.1:$port/" "$NGINX_CONF"

    # 测试并重载Nginx
    nginx -t || error "Nginx配置测试失败"
    nginx -s reload

    log "流量已切换至 $target"
}

# 6. 清理旧环境
cleanup_old() {
    local old_env=$1

    log "清理旧环境 $old_env"

    # 保留旧环境5分钟，以便快速回滚
    (
        sleep 300
        docker compose -f $DOCKER_COMPOSE_FILE -p $old_env down
        log "旧环境 $old_env 已清理"
    ) &
}

# ====== 主流程 ======
main() {
    log "========== 蓝绿部署开始 =========="

    ACTIVE_ENV=$(get_active_env)
    TARGET_ENV=$(get_target_env "$ACTIVE_ENV")

    # 端口映射
    if [ "$TARGET_ENV" = "$BLUE_ENV" ]; then
        TARGET_PORT=8000
    else
        TARGET_PORT=8001
    fi

    log "当前活跃: $ACTIVE_ENV → 部署目标: $TARGET_ENV (port: $TARGET_PORT)"

    deploy_target "$TARGET_ENV" "$TARGET_PORT"
    health_check "$TARGET_ENV" "$TARGET_PORT"
    switch_traffic "$TARGET_ENV" "$TARGET_PORT"
    cleanup_old "$ACTIVE_ENV"

    log "========== 蓝绿部署完成 =========="
    log "如需回滚，执行: ./rollback.sh $ACTIVE_ENV"
}

main "$@"
```

### 2.2 回滚脚本

```bash
#!/bin/bash
# scripts/rollback.sh
# 快速回滚到上一个环境

set -euo pipefail

APP_NAME="yian-shop"
NGINX_CONF="/etc/nginx/conf.d/${APP_NAME}.conf"

if [ $# -lt 1 ]; then
    echo "用法: $0 <blue|green>"
    exit 1
fi

TARGET=$1
PORT=$([ "$TARGET" = "${APP_NAME}-blue" ] && echo 8000 || echo 8001)

echo "[$(date)] 回滚到 $TARGET (port: $PORT)"

# 1. 检查目标环境是否还在运行
if ! docker ps --format '{{.Names}}' | grep -q "^${TARGET}"; then
    echo "错误: $TARGET 环境不存在，无法回滚"
    exit 1
fi

# 2. 切换Nginx
sed -i "s/proxy_pass http:\/\/[^;]*/proxy_pass http:\/\/$TARGET/" "$NGINX_CONF"
sed -i "s/server [^:]*:[0-9]*/server 127.0.0.1:$PORT/" "$NGINX_CONF"
nginx -t && nginx -s reload

echo "[$(date)] 回滚完成，流量已指向 $TARGET"
```

---

## 3. CI/CD修复

### 3.1 E2E Job修复（Alpine PHP包名）

```yaml
# .gitlab-ci.yml — E2E阶段修复
test_e2e:
  stage: test
  image: cypress/included:13.0.0
  services:
    - name: php:8.5-cli-alpine
      alias: backend
  variables:
    APP_ENV: testing
    DB_CONNECTION: sqlite
    DB_DATABASE: ':memory:'
  before_script:
    # 修复: Alpine 3.19+ 使用 php84 包名（PHP 8.4）或 php83（PHP 8.3）
    # 对于 php:8.5-cli-alpine 镜像，PHP已在镜像内，无需apk安装
    - php -v
    - php -m | grep -E "pdo|mysql|zip|gd|pcntl|redis"
    - cp .env.testing .env
    - composer install --no-interaction --prefer-dist --optimize-autoloader
    - php artisan migrate --force
    - php artisan db:seed --class=TestDataSeeder
    - php artisan serve --host=0.0.0.0 --port=8000 &
    - sleep 5 && curl -sf http://localhost:8000/api/v1/health || exit 1
  script:
    - npx cypress run --browser chrome --headless
  artifacts:
    when: always
    paths:
      - cypress/videos/
      - cypress/screenshots/
    expire_in: 7 days
  rules:
    - if: $CI_PIPELINE_SOURCE == "merge_request_event"
    - if: $CI_COMMIT_BRANCH == "staging"
```

### 3.2 补充CodeQL扫描

```yaml
# .gitlab-ci.yml — Security阶段补充
codeql_analysis:
  stage: security
  image: codeql/codeql-cli:latest
  variables:
    CODEQL_LANGUAGE: javascript,python  # 前端+后端
  script:
    - codeql database create codeql-db --language="$CODEQL_LANGUAGE" --source-root=.
    - codeql database analyze codeql-db --format=sarif-latest --output=codeql-results.sarif
    - codeql github upload-results --sarif=codeql-results.sarif --github-auth-stdin <<< "$GITHUB_TOKEN"
  artifacts:
    reports:
      sast: codeql-results.sarif
    expire_in: 30 days
  allow_failure: true
  rules:
    - if: $CI_COMMIT_BRANCH == "main"
    - if: $CI_COMMIT_BRANCH == "staging"
```

### 3.3 Docker镜像安全扫描

```yaml
# .gitlab-ci.yml — Security阶段补充
trivy_image_scan:
  stage: security
  image: aquasec/trivy:latest
  variables:
    TRIVY_SEVERITY: HIGH,CRITICAL
    TRIVY_EXIT_CODE: 0  # 不阻断构建，仅告警
  script:
    - docker build -t $CI_REGISTRY_IMAGE:$CI_COMMIT_SHA -f Dockerfile .
    - trivy image --severity $TRIVY_SEVERITY --exit-code $TRIVY_EXIT_CODE $CI_REGISTRY_IMAGE:$CI_COMMIT_SHA
  rules:
    - if: $CI_COMMIT_BRANCH == "main"
```

---

## 4. 域名/SSL证书操作

### 4.1 cert-manager自动续期（K8s环境）

```yaml
# k8s/cert-manager/certificate.yaml
apiVersion: cert-manager.io/v1
kind: Certificate
metadata:
  name: yian-shop-tls
  namespace: default
spec:
  secretName: yian-shop-tls-secret
  issuerRef:
    name: letsencrypt-prod
    kind: ClusterIssuer
  dnsNames:
    - www.yian.com
    - admin.yian.com
    - api.yian.com
    - cdn.yian.com
    - ws.yian.com
```

```yaml
# k8s/cert-manager/cluster-issuer.yaml
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-prod
spec:
  acme:
    server: https://acme-v02.api.letsencrypt.org/directory
    email: devops@yian.com
    privateKeySecretRef:
      name: letsencrypt-prod
    solvers:
      - dns01:
          alidns:
            accessKeyIdSecretRef:
              name: alidns-secret
              key: access-key-id
            accessKeySecretSecretRef:
              name: alidns-secret
              key: access-key-secret
```

### 4.2 acme.sh脚本（非K8s环境）

```bash
#!/bin/bash
# scripts/setup-ssl.sh

curl https://get.acme.sh | sh
~/.acme.sh/acme.sh --upgrade --auto-upgrade

# 阿里云DNS API
export Ali_Key="<your-aliyun-access-key-id>"
export Ali_Secret="<your-aliyun-access-key-secret>"

# 申请证书
~/.acme.sh/acme.sh --issue \
  -d www.yian.com \
  -d admin.yian.com \
  -d api.yian.com \
  --dns dns_ali \
  --keylength 2048

# 安装到Nginx
~/.acme.sh/acme.sh --install-cert \
  -d www.yian.com \
  --key-file /etc/nginx/ssl/www.yian.com.key \
  --fullchain-file /etc/nginx/ssl/www.yian.com.crt \
  --reloadcmd "nginx -s reload"

# 设置自动续期（默认已启用）
~/.acme.sh/acme.sh --cron
```

---

## 5. Nginx完整配置

```nginx
# /etc/nginx/nginx.conf (完整顶层配置)
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 4096;
    use epoll;
    multi_accept on;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # 日志格式
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for" '
                    'rt=$request_time uct="$upstream_connect_time" '
                    'uht="$upstream_header_time" urt="$upstream_response_time"';

    access_log /var/log/nginx/access.log main;

    # 基础优化
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/rss+xml application/atom+xml image/svg+xml;

    # Brotli (需要ngx_brotli模块)
    # brotli on;
    # brotli_comp_level 6;
    # brotli_types text/plain text/css text/xml application/json application/javascript application/rss+xml application/atom+xml image/svg+xml;

    # 限流
    limit_req_zone $binary_remote_addr zone=api:10m rate=100r/s;
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
    limit_req_zone $binary_remote_addr zone=upload:10m rate=20r/m;

    # SSL优化
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384';
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;
    ssl_session_tickets off;

    # OCSP Stapling
    ssl_stapling on;
    ssl_stapling_verify on;
    ssl_trusted_certificate /etc/nginx/ssl/chain.crt;
    resolver 8.8.8.8 8.8.4.4 valid=300s;
    resolver_timeout 5s;

    # 安全响应头
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self' https://api.yian.com;" always;

    # 引入站点配置
    include /etc/nginx/conf.d/*.conf;
}
```

---

*本文档为运维与部署手册的补充，与《运维与部署手册》配套使用。*
