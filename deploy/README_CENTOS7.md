## CentOS 7 部署指南（Docker Compose）

目标：在 CentOS 7 上用 Docker 运行本项目，数据库使用外部 MySQL。

### A. 安装 Docker Engine（示例流程）

（以下命令需要服务器可访问外网 yum 源；如果是内网环境，请按你们公司的离线安装方式执行。）

```bash
sudo yum install -y yum-utils device-mapper-persistent-data lvm2
sudo yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
sudo yum install -y docker-ce docker-ce-cli containerd.io
sudo systemctl enable --now docker
```

验证：
```bash
docker version
```

### B. 安装 Docker Compose

优先用：`docker compose`（Compose plugin）。如果你的环境只有 `docker-compose` 也可以，本项目脚本两者都支持。

验证：
```bash
docker compose version || docker-compose version
```

### C. 准备项目与配置

1) 上传代码到服务器，例如：`/opt/epay`

2) 配置外部数据库连接：
```bash
cd /opt/epay
cp .env.example .env
vi .env
```

至少填写：`DB_HOST`、`DB_USER`、`DB_PASS`、`DB_NAME`。

### D. 启动

```bash
chmod +x deploy/deploy.sh
./deploy/deploy.sh
```

默认端口是 8080（可在 `.env` 里改 `APP_PORT`）。

### E. 防火墙放行端口

如果你用 firewalld：
```bash
sudo firewall-cmd --permanent --add-port=8080/tcp
sudo firewall-cmd --reload
```

### F. 安装与安装锁

首次访问：
- `http://<服务器IP>:8080/`

如果提示未安装：
- `http://<服务器IP>:8080/install/`

安装完成后确认存在：`install/install.lock`。

### G. 生产建议（强烈）

- 用 Nginx 反代到容器端口，启用 HTTPS（证书）
- 后台管理路径与口令加固（不要用默认 admin/123456）
- 限制后台访问 IP（安全组/防火墙/Nginx allow/deny）
- 定时任务/回调地址务必走 HTTPS 并做好签名校验
