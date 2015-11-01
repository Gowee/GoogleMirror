# 谷歌镜像 #
**for English version,click [here](README.md).**
用 PHP 搭建一个谷歌镜像

---

#### 安装指南 ####

**部署：**
- 安装前准备 :
	- 运行环境： PHP 5.4+
	- 通配符证书（www. 的单域名证书也可）
	- 配置好SSL
- 下载文件
- 打开 public.php，并替换默认域名为你自己的域名 ($pHost = "yourdomain.com";)
- 上传到你的 PHP环境中

**注意：**
- 关于 DNS 记录: 至少需要 www. 指向，对于大流量服务器建议添加 ipv4. 和 ipv6. 指向以处理验证码。 
