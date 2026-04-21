## 一键部署脚本

- Linux / CentOS 7：`deploy/deploy.sh`
- Windows / PowerShell：`deploy/deploy.ps1`

### 1) 配置外部数据库
在项目根目录：

```bash
cp .env.example .env
```

编辑 `.env`，至少填写：
- `DB_HOST`
- `DB_USER`
- `DB_PASS`
- `DB_NAME`

可选：`APP_PORT`（默认 8080）。

### 2) 运行脚本

Linux：
```bash
chmod +x deploy/deploy.sh
./deploy/deploy.sh
```

Windows：
```powershell
PowerShell -ExecutionPolicy Bypass -File .\deploy\deploy.ps1
```

### 3) 可选：强制重建镜像

当你改了代码或 Dockerfile，需要重建镜像时：

Linux：
```bash
BUILD=1 ./deploy/deploy.sh
```

Windows：
```powershell
$env:BUILD=1; PowerShell -ExecutionPolicy Bypass -File .\deploy\deploy.ps1
```

### 4) CentOS 7 详细说明

看 `deploy/README_CENTOS7.md`。
