#!/bin/bash
# ============================================================
# 怡安印刷商城 — Staging 环境一键部署脚本
# 用法: ./scripts/deploy-staging.sh
# 目标: 阿里云 ECS 2核2G (47.121.199.11)
# 前置: 服务器已安装 Docker + Docker Compose
# ============================================================

set -e

# 配置
SERVER_HOST="47.121.199.11"
SERVER_USER="root"
REMOTE_DIR="/opt/yian-staging"
COMPOSE_FILE="docker-compose.staging.yml"

echo "========================================"
echo "怡安印刷商城 — Staging 环境部署"
echo "目标服务器: ${SERVER_HOST}"
echo "========================================"

# 检查 SSH 连接
echo "[1/6] 检查服务器连接..."
if ! ssh -o ConnectTimeout=5 -o BatchMode=yes ${SERVER_USER}@${SERVER_HOST} "echo ok" &> /dev/null; then
    echo "错误: 无法连接到服务器 ${SERVER_HOST}"
    echo "请确保:"
    echo "  1. SSH 密钥已配置: ssh-copy-id ${SERVER_USER}@${SERVER_HOST}"
    echo "  2. 服务器已开机且网络通畅"
    exit 1
fi

# 构建并推送代码
echo "[2/6] 同步代码到服务器..."
rsync -avz --delete \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs/*' \
    --exclude='storage/app/public/*' \
    --exclude='.env' \
    ./ ${SERVER_USER}@${SERVER_HOST}:${REMOTE_DIR}/

# 服务器端部署
echo "[3/6] 服务器端部署..."
ssh ${SERVER_USER}@${SERVER_HOST} << EOF
    set -e
    cd ${REMOTE_DIR}

    # 创建 .env 文件（如果不存在）
    if [ ! -f ".env" ]; then
        cp .env.development .env
        echo "⚠️  首次部署：请在服务器上编辑 ${REMOTE_DIR}/.env，设置正确的 APP_KEY 和数据库密码"
    fi

    # 拉取镜像并启动
    echo "  构建镜像..."
    docker compose -f ${COMPOSE_FILE} build --no-cache

    echo "  启动容器..."
    docker compose -f ${COMPOSE_FILE} up -d

    # 等待 MySQL
    echo "  等待数据库..."
    sleep 5
    until docker compose -f ${COMPOSE_FILE} exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; do
        echo "    等待 MySQL..."
        sleep 2
    done

    # 安装依赖（如无 vendor）
    if [ ! -d "vendor" ]; then
        echo "  安装 Composer 依赖..."
        docker compose -f ${COMPOSE_FILE} exec -T app composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
    fi

    # 运行迁移
    echo "  运行数据库迁移..."
    docker compose -f ${COMPOSE_FILE} exec -T app php artisan migrate --force

    # 缓存优化
    echo "  优化缓存..."
    docker compose -f ${COMPOSE_FILE} exec -T app php artisan config:cache
    docker compose -f ${COMPOSE_FILE} exec -T app php artisan route:cache
    docker compose -f ${COMPOSE_FILE} exec -T app php artisan view:cache

    # 清理旧镜像
    echo "  清理旧镜像..."
    docker image prune -f

    echo "  部署完成"
EOF

echo ""
echo "========================================"
echo "✅ Staging 部署成功！"
echo "========================================"
echo ""
echo "访问地址:"
echo "  🌐 Staging: http://${SERVER_HOST}"
echo ""
echo "服务器维护命令:"
echo "  查看日志:   ssh ${SERVER_USER}@${SERVER_HOST} 'cd ${REMOTE_DIR} && docker compose -f ${COMPOSE_FILE} logs -f'"
echo "  重启服务:   ssh ${SERVER_USER}@${SERVER_HOST} 'cd ${REMOTE_DIR} && docker compose -f ${COMPOSE_FILE} restart'"
echo "  进入容器:   ssh ${SERVER_USER}@${SERVER_HOST} 'cd ${REMOTE_DIR} && docker compose -f ${COMPOSE_FILE} exec app sh'"
echo ""
