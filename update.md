## 升级

修改excel导出方法
---
适配lumen避免lumen的laravel版本错误导致的问题
---
action 变化
===
action "**fields**" 关键字改为 "**prepare**"

执行规则 ==**exec + 首字母大写的action关键字**==

目前有以下方法 `execPrepare()` `execQuery()` `execExport()`

新增方法，属性及定义
---
- `getQueryCondition($name， $machOr = false)` 获取查询条件
`$name` 条件名称
`$machOr` boolean `true` 匹配多条件单元，若单元中包含，则返回该条件； `false`只匹配单条件单元

- `hasQueryCondition($name， $type = 0)` 判断查询条件是否存在
`$name` 条件名称
`$type` int 匹配规则， 0 任意匹配； 1 单条件单元匹配； 2 多条件单元匹配。

- `where($column, $opeartor, $value)`
添加查询条件，参数和`$query->where()`参数一样

修改方法，属性及定义
---

- `$joinDef` 回调方法参数由`$query`对象改变为`$join`对象

- `$customJoins` 重命名为`$joins`

- `$customFields` 重命名为`$fieldDef`

- `$hiddenFields` 重命名为`$hidden`

- `$whereDef` 回调方法参数格式为 `['name'=> '', 'fun'=> '', 'argv'=> [...]]`

- `prepare()` 在	`$action = 'prepare'` 时会调用此方法

- `$header[$name]['cascade']` `bool`型，是否使用级联选择

- `addWheres($query, $conditions = null, $bool = 'and')` 添加条件组 `$conditions = null` 默认区查询条件

移除方法，属性及定义
---

- `addoption()`
- `hasCondition()`
- `getQueryConditions()`