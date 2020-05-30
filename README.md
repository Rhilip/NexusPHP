# Better NexusPHP

这是一个个人`测试+练手`项目，基于个人愿望对原版NexusPHP进行改造的repo。

因为 Github上已有的 [ZJUT/NexusPHP](https://github.com/ZJUT/NexusPHP) 添加了 ucenter 组件并未提供相关数据库信息，
从 https://sourceforge.net/projects/nexusphp/ 下载 `nexusphp.v1.5.beta5.20120707` 并基于此版本进行改造。 

** 你应该明白：虽然本项目相关更改是有生产实践背景的，但是本repo展示的实现仅借鉴并展示了相关实践的思路，并没有在相关更改后进行有效测试。 **
** 因此，不建议其他人在任何时候使用。**

## 相关更新历史

| 时间 | 目的 | 介绍 | 相关commit或pr |
|:---:|:---:|:---:|:---:|
| 2020.05.30 | 修复前一commit错误，合并一些更新 | ... | <https://github.com/Rhilip/NexusPHP/pull/3> |
| 2020.02.07 | 支援PHP7 + Redis | [NexusPHP 建站优化 (3) 升级NPHP到PHP 7](https://blog.rhilip.info/archives/1188/) | <https://github.com/Rhilip/NexusPHP/pull/2> |
| 2020.02.06 | 更换Bencode库为`rhilip/bencode` | [NexusPHP 建站优化 (2) 替换 Bencode 库](https://blog.rhilip.info/archives/1187/)  |  <https://github.com/Rhilip/NexusPHP/pull/1> | 
| 2020.01.21 | 优化cleanup运行方式 | [NexusPHP 建站优化 (1) 自动清理 (cleanup)](https://blog.rhilip.info/archives/1178/) | <https://github.com/Rhilip/NexusPHP/commit/2a833ff18a149216c63123dcd359e034f2e7e859> |

# README FROM SOURCE
ABOUT NexusPHP
This Project NexusPHP is an open-sourced private tracker script written in PHP.
It forks from the TBSource project and some other open-sourced projects.
Please read the LICENSE file before using this project.
Read the INSTALL file for information about how to use it.
Read the RELEASENOTE file about this release.
Visit http://www.nexusphp.com for more information about this project.
