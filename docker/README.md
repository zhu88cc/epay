## Docker Compose 部署（外部数据库）

### 1) 准备环境变量
在项目根目录复制并填写：

```bash
cp .env.example .env
```

至少需要配置：`DB_HOST`、`DB_USER`、`DB_PASS`、`DB_NAME`。

可选：修改 `.env` 里的 `APP_PORT`（默认 8080）。

### 2) 构建并启动

```bash
docker compose up -d --build
```

默认监听本机 `8080` 端口：
- `http://127.0.0.1:8080/`

### 3) 安装/安全锁

程序检测到数据库未初始化时会提示访问 `/install/`。
安装完成后请确保 `install/install.lock` 存在（这是强制的安全保护）。

### 4) 说明

- `config.php` 会在容器启动时根据环境变量自动写入（覆盖容器内同名文件）。
- 如果你经常 `--build` 重新创建容器，建议把 `install/` 目录做持久化挂载（见 `docker-compose.yml` 里的 `volumes` 注释示例）。
