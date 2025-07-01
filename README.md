# AI管理分析系统服务端（企业微信版）

### 项目结构

```
app/                   应用服务
common/                公共目录
common/components/     组件
common/config/         配置
common/helpers/        助手类
common/models/         数据模型
common/services/       逻辑层
console/               命令行程序
```

### 域名解析

应用服务：ai-analysis-work-wechat.yuzhua-test.com

语言版本：PHP74

**HTTPS：是**

### 接口文档

地址：https://apifox.com/apidoc/project-4448518/preview

### 安装部署

```
git clone git@gitee.com:ai-management-analysis/ai-analysis-work-wechat.git
cd ai-analysis-work-wechat
git fetch origin xxxx:xxxx
git checkout xxxx

chmod 777 -R ./runtime
chmod 777 -R ./app/runtime
chmod 777 -R ./console/runtime

composer install
```

### Nginx配置

```
server {
    listen 80;
    server_name ai-analysis-work-wechat.yuzhua-test.com;
    root /data/wwwroot/ai-analysis-work-wechat/app/web;
    index index.php;
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9100;
        fastcgi_index index.php;
        include fastcgi.conf;
    }
}
```

### 依赖扩展

##### 依赖Python
- 目前只支持python3.6
- 使用前需要安装`pycryptodome`库
```
  pip3 install pycryptodome
```

##### 企业微信-会话内容存档PHP扩展：php7-wxwork-finance-sdk  （⚠️废弃）

###### 执行以下命令进行安装

```shell
 cd /www

 # 下载扩展源代码
 git clone https://github.com/pangdahua/php7-wxwork-finance-sdk.git

 # 下载扩展所需C语言SDK
 wget https://wwcdn.weixin.qq.com/node/wework/images/sdk_20200401.zip
 
 # 解压文件
 unzip sdk_20200401.zip
 
 # 进入php7-wxwork-finance-sdk项目内
 cd php7-wxwork-finance-sdk

 # 执行 phpize  
 /www/server/php/74/bin/phpize

 # 注意 php-config 和 C_sdk 路径
 ./configure --with-php-config=/www/server/php/74/bin/php-config --with-wxwork-finance-sdk=/www/sdk_20200401/C_sdk

 # 编译
 make && make install
```

###### 配置文件 [php.ini] 增加

```shell
 extension=wxwork_finance_sdk.so
```

### 数据库

##### MySQL

```sql
-- 设置database默认字符集
ALTER DATABASE `ai-analysis-work-wechat` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

```


### 企业微信配置项

##### 配置信息

- 服务商信息 - 基本信息 - IP白名单
- 企业主 在企业后台 设置应用权限


### 企业微信公钥和私钥环境变量配置说明

## 背景

为了提高系统安全性，我们将企业微信的公钥和私钥从代码中移动到环境变量中进行配置。这样可以避免敏感信息直接暴露在代码库中，同时也方便不同环境下的密钥管理。

### 仅支持(RSA-2048)密钥算法

## 修改内容

在 `common/config/params.php` 文件中，我们将原先硬编码的公钥和私钥替换为从环境变量中获取：

```php
# 消息加密公钥
'publicKey'  => env("WORK_WECHAT_PUBLIC_KEY"),
# 消息加密私钥
'privateKey' => env("WORK_WECHAT_PRIVATE_KEY")
```

## 环境变量配置

请在您的环境变量文件（如 `.env`、`.env.test` 等）中添加以下配置：

```
# 企业微信 消息加密公钥
WORK_WECHAT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
******
-----END PUBLIC KEY-----"

# 企业微信 消息加密私钥
WORK_WECHAT_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----
******
-----END PRIVATE KEY-----"
```

## 注意事项

1. 请确保环境变量中的公钥和私钥格式正确，包括换行符和开始/结束标记。
2. 在不同环境（开发、测试、生产）中，请分别配置对应的环境变量文件。
3. 请妥善保管私钥信息，避免泄露。
4. 如果您使用的是容器化部署，请确保在容器配置中正确设置这些环境变量。
