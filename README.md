# TVBox 配置解密工具

一个基于 PHP 的 TVBox 配置接口解密页面。项目提供本地自动解密、外部接口解密、解密结果复制/下载，以及 Jar 文件代理下载能力。

## 功能

- 本地自动解密 TVBox 配置接口。
- 支持普通 JSON、TVBox AES-128-CBC、Base64、多仓、图片尾部 Base64 payload 等常见格式识别。
- 支持通过外部接口解密：
  - FongMi
  - 饭太硬
- 支持复制解密结果。
- 支持下载解密结果，JSON 内容保存为 `.json`，其他内容保存为 `.txt`。
- 支持从解密结果中的 `spider` 字段下载 Jar 文件。
- 前端页面无内置测试地址，需要用户自行输入配置地址。

## 文件结构

```text
.
├── index.php                      # 调用页面，负责界面和前端交互
├── local.php                      # 本地解密接口
└── api.php                        # 外部接口解密与 Jar 下载接口
```

## 环境要求

- PHP 7.0 或更高版本。
- PHP 扩展：
  - `curl`
  - `openssl`
  - `mbstring` 推荐启用
  - `iconv` 推荐启用

## 本地运行

在项目目录执行：

```bash
php -S 127.0.0.1:8000 -t .
```

然后访问：

```text
http://127.0.0.1:8000/index.php
```

## 使用方式

1. 打开 `index.php` 页面。
2. 输入 TVBox 配置地址。
3. 选择解密方式：
   - 本地自动解密
   - FongMi 接口解密
   - 饭太硬接口解密
4. 点击“开始解密”。
5. 根据需要复制结果、下载结果或下载 Jar。

## 接口说明

### 本地解密

```text
local.php?url=配置地址
```

返回纯解密内容。

获取元信息：

```text
local.php?meta=1&url=配置地址
```

### 外部接口解密

```text
api.php?action=crawl&interface=fongmi&url=配置地址
api.php?action=crawl&interface=fantaiying&url=配置地址
```

返回纯解密内容。

获取元信息：

```text
api.php?action=crawl&interface=fongmi&meta=1&url=配置地址
api.php?action=crawl&interface=fantaiying&meta=1&url=配置地址
```

### Jar 下载代理

```text
api.php?action=download_jar&url=Jar地址
```

如果 Jar 地址包含 `;md5;` 后缀，接口会自动去除该后缀后再下载。

## 部署

把以下文件上传到支持 PHP 的 Web 环境即可：

```text
index.php
local.php
api.php
```

## 注意事项

- 外部接口解密依赖对应第三方服务的可用性。
- 如果服务器禁止外部 HTTP 请求，解密和 Jar 下载可能失败。
- 本工具仅用于个人学习与配置调试，请勿用于非法用途。
- 使用、部署或二次修改本项目时，必须保留页面底部版权信息，不得删除、隐藏或遮挡版权声明与作者链接。

## 版权

Copyright © 2026 [木子白白白](https://github.com/hnlyzxf). All rights reserved.
