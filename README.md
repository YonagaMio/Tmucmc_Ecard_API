# TMUCMC

**天津医科大学临床医学院** 电子一卡通相关接口的封装与实现

本项目提供了以下功能接口：

- 获取用户 Token
- 获取用户详细信息
- 获取院系信息
- 获取浴室列表
- 获取预约码
- 取消预约码

**机器码用水** 和 **扫码用水** 的接口尚未抓取

后续计划添加：

- **账单信息**
- **照明与空调电费信息查询及缴纳**
- **etc...**

感兴趣可自行抓包PR

接口所需的**具体参数**和**接口链接**已在 _index.php_ 中详细描述
<br>
**Json目录下包含官方接口在多种情况下的详细返回参数文件** 
<br>
**（涵盖基本场景，不保证完全齐全）** 
<br>
**Json中的"//" 键为自行添加的注释，非原版数据**

需要改版可自行查看

**如有任何错误或疑问，提issue或PR进行修正**

**~~同学，学号给一下，加学分~~**

测试环境：

- PHP 8.2

各功能接口示例请求地址：

```php
// 获取UserToken
http(s)://domain.com/api.php?type=getToken&studentId=xxx&password=xxx
// 获取学生详细信息
http(s)://domain.com/api.php?type=getDetailInfo&token=xxx
// 获取院系信息
http(s)://domain.com/api.php?type=getDepartmentInfo&cardNum=xxx&token=xxx
// 获取浴室列表
http(s)://domain.com/api.php?type=getBathList&cardNum=xxx
// 获取预约码
http(s)://domain.com/api.php?type=getReservationCode&cardNum=xxx&classno=xxx
// 取消预约
http(s)://domain.com/api.php?type=cancelReservation&cardNum=xxx&classno=xxx
```

# 免责声明

**如有侵权，请通过邮件 <ocesux@gmail.com> 联系我删除** 

**本项目及所提供的接口仅供学习、研究和非商业用途使用。** 

**使用者需确保遵守相关法律法规及天津医科大学临床医学院的相关规定。** 

如你所见，本项目使用 **MIT License** 开源 

**因使用本软件而引致的任何意外、疏忽、合约毁坏、诽谤、版权或知识产权侵犯及其所造成的任何损失，本人概不负责，亦概不承担任何民事或刑事法律责任。** 

**以上内容，本人保留最终解释权。**
