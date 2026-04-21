#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "$1 not found. Please install it first." >&2
    exit 1
  }
}

get_env_value() {
  local key="$1"
  # 读取形如 KEY=value 的最后一个匹配行；不 source .env（避免执行任意代码）
  sed -n "s/^${key}=//p" "$PROJECT_ROOT/.env" | tail -n 1 | tr -d '\r'
}

require_env() {
  local key="$1"
  local val
  val="$(get_env_value "$key" || true)"
  if [[ -z "${val}" ]]; then
    echo "Missing or empty ${key} in .env" >&2
    exit 1
  fi
}

echo "[1/5] Checking docker..."
need_cmd docker

docker info >/dev/null 2>&1 || {
  echo "Docker daemon not running or permission denied." >&2
  echo "Try: sudo systemctl start docker" >&2
  exit 1
}

compose_cmd=""
if docker compose version >/dev/null 2>&1; then
  compose_cmd="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
  compose_cmd="docker-compose"
else
  echo "docker compose / docker-compose not found. Please install docker-compose." >&2
  exit 1
fi

cd "$PROJECT_ROOT"

echo "[2/5] Preparing .env ..."
if [[ ! -f .env ]]; then
  if [[ -f .env.example ]]; then
    cp -n .env.example .env
    echo "Created .env from .env.example." >&2
    echo "Please edit .env (DB_HOST/DB_USER/DB_PASS/DB_NAME) then re-run." >&2
    exit 1
  else
    echo ".env.example is missing." >&2
    exit 1
  fi
fi

# 必填项校验
require_env DB_HOST
require_env DB_USER
require_env DB_NAME
# DB_PASS 允许为空（有些内网 MySQL 可能无密码，但不推荐）

# 可选：使用 BUILD=1 强制重建镜像
build_flag=""
if [[ "${BUILD:-0}" == "1" ]]; then
  build_flag="--build"
fi

echo "[3/5] Starting containers..."
$compose_cmd up -d $build_flag

echo "[4/5] Status"
$compose_cmd ps

echo "[5/5] Done."
app_port="$(get_env_value APP_PORT || true)"
app_port="${app_port:-8080}"
echo "Open: http://YOUR_SERVER_IP:${app_port}/"
echo "First install (if needed): http://YOUR_SERVER_IP:${app_port}/install/"
