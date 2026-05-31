#!/bin/bash
# ============================================================
# 怡安印刷商城 — 本地开发环境一键启动脚本
# 用法: ./scripts/setup-dev.sh [full|lite]
#   full - 完整版 (推荐，需要 16GB+ 内存)
#   lite - 精简版 (8GB 内存适配)
# ============================================================

set -e

COMPOSE_FILE="docker-compose.dev.yml"
MODE="完整版"

if [ "$1" = "lite" ]; then
    COMPOSE_FILE="docker-compose.dev-lite.yml"
    MODE="精简版"
fi

echo "========================================"
echo "怡安印刷商城 — 开发环境启动 (${MODE})"
echo "========================================"

# 检查 Docker
echo "[1/7] 检查 Docker 环境..."
if ! command -v docker &> /dev/null; then
    echo "错误: Docker 未安装，请先安装 Docker Desktop"
    exit 1
fi
if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    echo "错误: Docker Compose 未安装"
    exit 1
fi

# 检查 .env 文件
echo "[2/7] 检查环境配置..."
if [ ! -f ".env" ]; then
    echo "创建 .env 文件..."
    cp .env.development .env
    echo "已复制 .env.development -> .env"
    echo "⚠️  请编辑 .env 文件，将 APP_KEY 替换为随机字符串:"
    echo "   php -r \"echo 'APP_KEY=' . base64_encode(random_bytes(32)) . PHP_EOL;\""
fi

# 构建镜像
echo "[3/7] 构建 Docker 镜像..."
docker compose -f ${COMPOSE_FILE} build --no-cache

# 启动服务
echo "[4/7] 启动容器..."
docker compose -f ${COMPOSE_FILE} up -d

# 等待 MySQL 就绪
echo "[5/7] 等待数据库就绪..."
sleep 5
until docker compose -f ${COMPOSE_FILE} exec -T mysql mysqladmin ping -h localhost --silent; do
    echo "  等待 MySQL..."
    sleep 2
done
echo "  MySQL 已就绪"

# 安装 Composer 依赖
echo "[6/7] 安装 PHP 依赖..."
docker compose -f ${COMPOSE_FILE} exec -T app composer install --no-interaction --prefer-dist

# 运行迁移和种子
echo "[7/7] 运行数据库迁移和种子..."
docker compose -f ${COMPOSE_FILE} exec -T app php artisan migrate --force
docker compose -f ${COMPOSE_FILE} exec -T app php artisan db:seed --force

echo ""
echo "========================================"
echo "✅ 开发环境启动成功！"
echo "========================================"
echo ""
echo "服务访问地址:"
echo "  🌐 后端 API:    http://localhost:8080"
echo "  📧 Mailpit:     http://localhost:8025"
echo "  🔍 Meilisearch: http://localhost:7700"
echo "  🗄️  MySQL:      localhost:3306 (user: yian / pass: yian)"
echo "  💾 Redis:       localhost:6379"
echo ""
echo "常用命令:"
echo "  查看日志:   docker compose -f ${COMPOSE_FILE} logs -f"
echo "  进入容器:   docker compose -f ${COMPOSE_FILE} exec app bash"
echo "  运行 Artisan: docker compose -f ${COMPOSE_FILE} exec app php artisan ..."
echo "  停止环境:   docker compose -f ${COMPOSE_FILE} down"
echo ""
