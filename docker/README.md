## Docker Compose 部署（外部数据库）

### 1) 准备环境变量

复制并填写：

```bash
cp .env.example .env
```

至少需要配置：`DB_HOST`、`DB_USER`、`DB_PASS`、`DB_NAME`。

### 2) 构建并启动

```bash
docker compose up -d --build
```

默认监听本机 `8080` 端口：
- `http://127.0.0.1:8080/`

### 3) 安装/升级

程序检测到数据库未初始化时，会提示访问 `/install/`。

安装完成后请确保 `install/install.lock` 存在（安全要求）。

### 4) 说明

- `config.php` 会在容器启动时根据环境变量自动写入（覆盖容器内的同名文件）。
- 如需持久化缓存/日志/锁文件，可在 `docker-compose.yml` 里取消注释 `volumes`。

