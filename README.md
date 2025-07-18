# SCRM（基于企业微信生态，企微会话存档服务）

> 圆弧scrm，是一款企业微信为基础（含会话存档），AI赋能的微信私域运营开放平台，我们致力于帮助企业时间引流拓客，精准运营，同时通过AI能力，更便捷高效的完成客户管理和员工管理。


## 一、 系统功能介绍

### 1\. 系统功能结构

#### 基础功能

| 功能 | 描述 |
| :--- | :--- |
| 企业账号 | SaaS板块，管理各个入驻企业的账号内容及应用基础能力 |
| 套餐管理 | SaaS板块，企微第三方应用的套餐版本管理，用于企业基础角色的创建和应用 |
| 权限管理 | SaaS板块，系统所有功能、应用权限的管理和维护 |
| 员工权限 | 企微账号的系统权限配置管理，以及企微接口能力的分配 |
| 角色管理 | 企业下，角色菜单功能权限分配、设置角色按机构进行数据范围权限划分 |
| 操作日志 | 系统正常操作日志记录和查询 |
| 企业详情 | 企业基础信息，企微官方信息及应用能力的展示和维护 |
| 企微交接 | 企微官方互通接口及会话存档内容的转移 |
| 客户交接 | 客户相关数据及资料的交接 |

#### 会话管理

| 功能 | 描述 |
| :--- | :--- |
| 会话轨迹 | 员工会话内容概要统计，如单日会话客户数，会话次数等 |
| 员工会话 | 企微员工列表，查询会话详情内容，查询员工会话客户 |
| 客户会话 | 外部联系人列表，定位客户会话存档详情，客户与crm关联逻辑 |
| 群聊会话 | 企业内群聊创建人的群聊管理及会话详情 |
| 离职会话 | 离职员工列表，查看和客户的历史会话内容 |
| 拒绝存档 | 拒绝会话存档客户列表，记录单聊和群聊中拒绝客户的记录 |

#### 客户管理

| 功能 | 描述 |
| :--- | :--- |
| 商机管理 | 用于查看和管理系统商机，支持商机联系人同步、AI 分析及打标签操作。 |
| 联系人管理 | 用于管理企业微信内外部联系人，支持联系人信息整合、标签分类及检索操作。 |
| 客户管理 | 用于管理客户全生命周期，支持客户信息建档、需求分析及满意度评估操作。 |
| 线索管理 | 用于管理多渠道线索，支持线索收集、筛选、评分及处理进度监控操作。 |
| 公海 | 用于管理闲置客户资源，支持客户资源回收、重新分配及共享池管理操作。 |

#### AI应用

| 功能 | 描述 |
| :--- | :--- |
| 风险预警 | 员工会话中触发风险操作或敏感词时，对应生成的记录列表 |
| 情绪识别 | 识别单次会话存档中的客户和员工情绪并记录 |
| 员工评分 | AI对单次员工会话中，员工的表现进行评分 |
| 沟通关键词 | AI对单次会话中，沟通核心关键词进行归纳记录 |
| 竞品分析 | AI对单次会话中，客户提及竞品内容进行归纳记录 |
| 会话AI分析 | 基于会话的AI分析与结果适配 |
| 商机AI分析 | 基于商机的AI分析与结果适配 |
| AI分析设置 | 基础AI分析规则的管理和配置 |
| 行业适配 | AI模型调用逻辑，即系统应用场景 |
| 知识库设置 | 企业行业数据字典及知识库管理及配置 |
| 敏感词库 | 管理和维护企业敏感词列表 |
| 资料库 | 企业内容资料的管理和应用 |
| 智能体管理 | 分析智能体及分析策略的管理及配置 |

#### 客户营销

| 功能 | 描述 |
| :--- | :--- |
| 渠道活码 | 基于企微官方能力，快捷建码方便客户快速加好友 |
| 群活码 | 基于企微官方能力，管理群二维码，方便客户快捷入群 |
| 欢迎语 | 基于企微官方能力，用于客户不容场景下的快捷回复 |
| 朋友圈 | 基于企微官方能力，用于管理查询员工朋友圈信息，支持sop发送 |
| 群发助手 | 基于企微官方能力，用于员工群发消息几资料，支持sop发送 |

## 二、  系统演示

企业微信第三方应用市场搜索：圆弧scrm，圆弧会话存档；企微管理员添加后即可体验免费版。

![](https://public.yuzhua.com/ai-analysis-work-wechat/up/1.png)

## 三、  系统技术栈

### 1\. 前端技术栈

| 技术栈 | 介绍 |
| :--- | :--- |
| Vue | 渐进式 JavaScript 框架 |
| Vant | 轻量、可靠的移动端 Vue 组件库 |
| Element-UI | 基于 Vue 的桌面端组件库 |

### 2\. 后端技术栈

| 技术栈 | 介绍 |
| :--- | :--- |
| PHP | 基于7.4版本 |
| MYSQL | 要求5.7版本级以上 |
| Redis | Key-Value数据库 |
| Yii Framework | 大型 Web 应用的高性能 PHP 框架 |
| RabbitMq | 基于AMQP协议的消息中间件 |
| Jaeger | 分布式跟踪系统 |
| Tablestore | 阿里云提供的Serverless分布式NoSQL数据库服务 |
| OSS | 对象存储 |

### 3\. 后端代码部署

#### 安装部署

```
git clone git@gitee.com:yuanhusmart/scrm.git
cd scrm
git fetch origin xxxx:xxxx
git checkout xxxx

chmod 777 -R ./runtime
chmod 777 -R ./app/runtime
chmod 777 -R ./console/runtime

composer install
```

#### Nginx配置

```
server {
    listen 80;
    server_name scrm.com;
    root /your/path/to/scrm;
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


## 四、  系统截图

| 模块 | 图例 | 图例 |
| :--- | :--- | :--- |
| 首页 | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/11.png) | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/12.png) |
| 商机 | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/21.png) | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/22.png) |
| 商机分析 | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/31.png) | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/32.png) |
| 联系人 | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/41.png) | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/42.png) |
| 会话管理 | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/51.png) | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/52.png) |
| 消息管理 | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/61.png) | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/62.png) |
| 会话分析 | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/71.png) | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/72.png) |
| 基础配置 | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/81.png) | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/82.png) |
| AI分析配置 | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/91.png) | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/92.png) |
| 知识集和敏感词 | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/101.png) | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/102.png) |
| 标签库和话术库 | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/111.png) | ![](https://public.yuzhua.com/ai-analysis-work-wechat/up/112.png) |

## 五、  迭代计划

| 版本号 | 内容摘要 | 时间 |
| :--- | :--- | :--- |
| v1.0.2 | 获客渠道拓展，完善官方拓客能力（渠道码、获客助手...），拓展外部渠道对接（百度、小红书、抖音..） | 2025-08 |
| v1.0.3 | 嵌入式AI应用，以智能体形式开放多个只能机器人助手，含：录音转文字、智能回复、归因分析助手、销冠大师、战败分析助手... | 2025-09 |
| v1.0.4 | 完善系统应用能力，通知、订单、工单、商品模块.... | 2025-10 |
| ...... |  |  |

## 六、  其他

**圆弧SCRM的诞生**

四川圆弧信息科技有限公司成立于2020年4月，也是四川鱼爪集团下的一个独立品牌。

我们在10多年的市场探索和客户服务中，拓展了很多业务类型，其中包含了网店服务、商标服务、企业服务和代运营等B端市场的热门业务，在对接客户的过程中，发现解决“企业-员工-客户”连接的问题，离不开CRM，但调研了市面上的大部分CRM系统，发现很少有系统能完美符合我们如此复杂多样的业务场景，所以我们决定自研。

在实际的系统应用中，我们又迭代了很多附加功能用来更贴合不同业务场景下的员工使用；这期间我们也有很多想法，有的我们做了一些功能落地。

* 比如用AI分析员工的能力和进阶的建议，我们希望AI能帮助我们把更多员工变为销冠。
* 比如用AI帮助员工做客户总结、客户标签、降低员工的操作成本，提高客户的管理效率。
* 比如做一个更泛用于的话术助手，在不同的业务场景下，通过销售的表达意图和客户的消息，推荐几个有效用的话术。
* 也有一些并没能落地，我们在迭代的过程中也在尽量找到一些解决方案。
* 比如做一个AI的行业分析专家，他能分析当前的行业形势，市场的利好面在哪里，校准当前公司的商业模式。
* 比如做一个AI的智能管理，他能通过公司的所有用户数据，员工行为数据，行业特性等，对各个部门、管理者、员工等角色进行定期的能力和能效的评估，给出不足之处的说明以及改进方向的建议，让企业管理者的管理成本有效降低。

系统应用逐渐稳定以后，我们考虑也许我们的项目经验和不同行业的解决方案，也许可以帮助更多企业，所以我们以最小的人力开发单元做了项目的封装，并对代码进行开源，当然从公司层面出发，这也是我们对新的业务领域做一次尝试。

**为什么选择企业微信**

在实际的应用场景中，不论是客户跟进情况的监听还是员工的实际销售表现数据化，所有的分析和监听，都需要会话记录的支持，为了有效且合法的获取会话信息，我们尽可能的把客户资源沉淀在企业微信内，这样也避免了crm与会话数据的割裂，方便后续拓展不同行业私域运营的业务形态。

**圆弧SCRM和普通SCRM有什么不同？**

我们在SCRM的基础功能模块上，增加了AI能力，从员工管理，客户管理，日常跟进，能力判定等各个方面都由AI做了协同处理，你可以理解为圆弧更像是SCRM+AI能力的开放功能增强版。 同时圆弧在SCRM基础上做开放接口，方便企业将原有系统更好的和SCRM系统进行融合。

**联系我们**

![](https://public.yuzhua.com/ai-analysis-work-wechat/up/zrz.jpg)

