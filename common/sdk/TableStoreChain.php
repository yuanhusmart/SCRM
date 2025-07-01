<?php

namespace common\sdk;

use Aliyun\OTS\Consts\AggregationTypeConst;
use Aliyun\OTS\Consts\ColumnReturnTypeConst;
use Aliyun\OTS\Consts\FieldTypeConst;
use Aliyun\OTS\Consts\GroupByTypeConst;
use Aliyun\OTS\Consts\OperationTypeConst;
use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use Aliyun\OTS\Consts\QueryTypeConst;
use Aliyun\OTS\Consts\RowExistenceExpectationConst;
use Aliyun\OTS\Consts\ScoreModeConst;
use Aliyun\OTS\Consts\SortModeConst;
use Aliyun\OTS\Consts\SortOrderConst;
use Aliyun\OTS\OTSClient;
use Aliyun\OTS\Retry\DefaultRetryPolicy;
use common\errors\Code;
use common\errors\ErrException;

/**
 * TableStore链式调用封装类
 * Class TableStoreChain
 * @package common\sdk
 */
class TableStoreChain
{

    /**
     * @var array 查询请求参数
     */
    protected $request = [];

    /**
     * @var array 查询条件
     */
    public $where = [];

    /**
     * @var array 排序条件
     */
    protected $sort = [];

    /**
     * @var int 偏移量
     */
    protected $offset = 0;

    /**
     * @var int 每页数量
     */
    protected $limit = 20;

    /**
     * @var string 表名
     */
    protected $tableName = '';

    /**
     * @var string 索引名
     */
    protected $indexName = '';

    /**
     * @var string 主键
     */
    protected $primaryKey = '';

    /**
     * @var array 返回字段
     */
    protected $returnNames = [];

    /**
     * @var array 索引架构
     */
    protected $indexSchema = [];

    /**
     * @var string 分页token
     */
    protected $nextToken = '';

    /**
     * @var array 批量写入数据
     */
    protected $batchWriteData = [];

    /**
     * @var string SQL查询语句
     */
    protected $sqlQuery = '';

    /**
     * @var array 聚合查询参数
     */
    protected $aggregations = [];

    /**
     * @var array 分组查询参数
     */
    protected $groupBys = [];

    /**
     * @var array 高亮参数
     */
    protected $highlight = [];

    /**
     * @var array 并行扫描参数
     */
    protected $parallelScan = [];

    /**
     * 查询类型常量
     */
    const QUERY_TYPE_MUST     = 'must';
    const QUERY_TYPE_SHOULD   = 'should';
    const QUERY_TYPE_MUST_NOT = 'must_not';

    /**
     * 排序方向常量
     */
    const SORT_DESC = 'desc';
    const SORT_ASC  = 'asc';

    /**
     * 排序模式常量
     */
    const SORT_MODE_AVG = 'avg';
    const SORT_MODE_MIN = 'min';
    const SORT_MODE_MAX = 'max';

    /**
     * 聚合类型常量
     */
    const AGG_MIN            = 'min';
    const AGG_MAX            = 'max';
    const AGG_SUM            = 'sum';
    const AGG_AVG            = 'avg';
    const AGG_COUNT          = 'count';
    const AGG_DISTINCT_COUNT = 'distinct_count';
    const AGG_PERCENTILES    = 'percentiles';
    const AGG_TOP_ROWS       = 'top_rows';

    /**
     * 分组类型常量
     */
    const GROUP_BY_FIELD        = 'field';
    const GROUP_BY_RANGE        = 'range';
    const GROUP_BY_FILTER       = 'filter';
    const GROUP_BY_GEO_DISTANCE = 'geo_distance';
    const GROUP_BY_HISTOGRAM    = 'histogram';

    /**
     * 创建标准字段配置
     *
     * @param string $fieldName 字段名称
     * @param string $fieldType 字段类型
     * @param bool $index 是否索引
     * @param bool $enableSortAndAgg 是否支持排序和聚合
     * @param bool $store 是否存储
     * @param bool $isArray 是否数组
     * @param array $fieldSchemas 子字段配置
     * @param string $analyzer 分析器
     * @param bool $isVirtualField 是否为虚拟列
     * @param string $sourceFieldNames 虚拟列对应的数据表中字段。
     * @param string $dateFormats 日期格式。 支持自定义格式。常用日期格式如下：yyyy-MM-dd HH:mm:ss.SSS、yyyyMMdd HHmmss
     * @return array 字段配置
     */
    public static function createFieldSchema(
        string $fieldName,
        string $fieldType,
        bool   $index = true,
        bool   $enableSortAndAgg = true,
        bool   $store = false,
        bool   $isArray = false,
        array  $fieldSchemas = [],
        string $analyzer = "",
        bool   $isVirtualField = false,
        string $sourceFieldNames = "",
        array  $dateFormats = []
    ): array
    {
        return [
            "field_name"          => $fieldName,
            "field_type"          => $fieldType,
            "field_schemas"       => $fieldSchemas,
            "analyzer"            => $analyzer,
            "index"               => $index,
            "enable_sort_and_agg" => $enableSortAndAgg,
            "store"               => $store,
            "is_array"            => $isArray,
            "is_virtual_field"    => $isVirtualField,
            "source_field_names"  => $sourceFieldNames,
            "date_formats"        => $dateFormats,
        ];
    }

    /**
     * 创建关键字类型字段配置
     *
     * @param string $fieldName 字段名称
     * @param bool $index 是否索引
     * @return array 字段配置
     */
    public static function createKeywordField(string $fieldName, bool $index = true): array
    {
        return self::createFieldSchema($fieldName, FieldTypeConst::KEYWORD, $index);
    }

    /**
     * 创建长整型字段配置
     *
     * @param string $fieldName 字段名称
     * @return array 字段配置
     */
    public static function createLongField(string $fieldName, bool $index = true): array
    {
        return self::createFieldSchema($fieldName, FieldTypeConst::LONG, $index);
    }

    /**
     * 创建日期字段配置
     *
     * @param string $fieldName 字段名称
     * @return array 字段配置
     */
    public static function createDateField(string $fieldName, array $dateFormats = [], bool $index = true, bool $isVirtualField = false, string $sourceFieldNames = ""): array
    {
        return self::createFieldSchema($fieldName, FieldTypeConst::DATE, $index, true, true, false, [], '', $isVirtualField, $sourceFieldNames, $dateFormats);
    }

    /**
     * 创建嵌套类型字段配置
     *
     * @param string $fieldName 字段名称
     * @param array $fieldSchemas 子字段配置
     * @param bool $index 是否索引
     * @return array 字段配置
     */
    public static function createNestedField(string $fieldName, array $fieldSchemas, bool $index = false): array
    {
        return self::createFieldSchema($fieldName, FieldTypeConst::NESTED, $index, false, false, false, $fieldSchemas);
    }

    /**
     * 创建文本类型字段配置
     *
     * @param string $fieldName 字段名称
     * @param string $analyzer 分析器
     * @return array 字段配置
     */
    public static function createTextField(string $fieldName, string $analyzer = "", bool $index = true): array
    {
        return self::createFieldSchema($fieldName, FieldTypeConst::TEXT, $index, false, false, false, [], $analyzer);
    }

    /**
     * 默认表名
     */
    const DEFAULT_TABLE_NAME = 'ots_suite_work_wechat_chat_data';

    /**
     * 默认索引名
     */
    const DEFAULT_TABLE_INDEX_NAME = 'ots_suite_work_wechat_chat_data_index';

    /**
     * 默认主键
     */
    const DEFAULT_PRIMARY_KEY = 'msgid';

    /**
     * TableStoreChain constructor.
     * @param string $tableName 表名
     * @param string $indexName 索引名
     * @param string $primaryKey 主键
     * @param array $defaultReturnField 默认返回字段
     */
    public function __construct(string $tableName = '', string $indexName = '', string $primaryKey = '')
    {
        $this->tableName  = $tableName ?: self::DEFAULT_TABLE_NAME;
        $this->indexName  = $indexName ?: self::DEFAULT_TABLE_INDEX_NAME;
        $this->primaryKey = $primaryKey ?: self::DEFAULT_PRIMARY_KEY;
    }

    /**
     * 创建客户端
     * @return OTSClient
     */
    public function OtsCreateClient(): OTSClient
    {
        $otsConfig = \Yii::$app->params['ali']['ots'];
        $otsArgs   = [
            'EndPoint'          => $otsConfig['endpoint'],
            'AccessKeyID'       => $otsConfig['accessKeyId'],
            'AccessKeySecret'   => $otsConfig['accessKeySecret'],
            'InstanceName'      => $otsConfig['instanceName'],
            // 以下是可选参数
            'ConnectionTimeout' => 15.0,                          # 与OTS建立连接的最大延时，默认 2.0秒
            'SocketTimeout'     => 15.0,                          # 每次请求响应最大延时，默认2.0秒
            // 重试策略，默认为 DefaultRetryPolicy
            // 如果要关闭重试，可以设置为： 'RetryPolicy' => new NoRetryPolicy(),
            // 如果要自定义重试策略，你可以继承 \Aliyun\OTS\Retry\RetryPolicy 接口构造自己的重试策略
            'RetryPolicy'       => new DefaultRetryPolicy(),
            // Error级别日志处理函数，用来打印OTS服务端返回错误时的日志 如果设置为null则为关闭log
            'ErrorLogHandler'   => '',
            //'ErrorLogHandler'   => "defaultOTSErrorLogHandler",
            // Debug级别日志处理函数，用来打印正常的请求和响应信息： 如果设置为null则为关闭log，defaultOTSDebugLogHandler 则输出
            'DebugLogHandler'   => '',
            //'DebugLogHandler'   => 'defaultOTSDebugLogHandler',
        ];
        return new OTSClient($otsArgs);
    }

    /**
     * 设置表名
     * @param string $tableName
     * @return $this
     */
    public function table(string $tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * 设置索引名
     * @param string $indexName
     * @return $this
     */
    public function index(string $indexName)
    {
        $this->indexName = $indexName;
        return $this;
    }

    /**
     * 设置索引架构
     * @param array $indexSchema
     * @return $this
     */
    public function indexSchema(array $indexSchema)
    {
        $this->indexSchema = $indexSchema;
        return $this;
    }

    /**
     * 设置分页
     * @param int $page
     * @param int $perPage
     * @return $this
     */
    public function page(int $page, int $perPage = 20)
    {
        $this->offset = ($page - 1) * $perPage;
        $this->limit  = $perPage;
        return $this;
    }

    /**
     * 设置偏移量和限制
     * @param int $offset
     * @param int $limit
     * @return $this
     */
    public function offsetLimit(int $offset, int $limit)
    {
        $this->offset = $offset;
        $this->limit  = $limit;
        return $this;
    }

    /**
     * 设置返回字段
     * @param array $returnNames
     * @return $this
     */
    public function select(array $returnNames)
    {
        $this->returnNames = $returnNames;
        return $this;
    }

    /**
     * 设置分页token
     * @param string $token
     * @return $this
     */
    public function token(string $token)
    {
        $this->nextToken = $token;
        return $this;
    }

    /**
     * 添加排序
     * @param string $field 字段名
     * @param string $order 排序方式 desc/asc
     * @param string $mode 排序模式
     * @return $this
     */
    public function orderBy(string $field, string $order = self::SORT_DESC, string $mode = self::SORT_MODE_AVG)
    {
        $orderConst = $order === self::SORT_DESC ? SortOrderConst::SORT_ORDER_DESC : SortOrderConst::SORT_ORDER_ASC;
        $modeConst  = SortModeConst::SORT_MODE_AVG;

        switch ($mode) {
            case self::SORT_MODE_MIN:
                $modeConst = SortModeConst::SORT_MODE_MIN;
            break;
            case self::SORT_MODE_MAX:
                $modeConst = SortModeConst::SORT_MODE_MAX;
            break;
        }

        $this->sort[] = [
            'field_sort' => [
                'field_name' => $field,
                'order'      => $orderConst,
                'mode'       => $modeConst,
            ]
        ];

        return $this;
    }

    /**
     * 添加精确匹配条件
     * @param string $field 字段名
     * @param mixed $value 值
     * @return $this
     */
    public function whereTerm(string $field, $value)
    {
        $this->where[] = [
            'query_type' => QueryTypeConst::TERM_QUERY,
            'query'      => [
                'field_name' => $field,
                'term'       => $value
            ]
        ];
        return $this;
    }

    /**
     * 添加多值匹配条件
     * @param string $field 字段名
     * @param array $values 值数组
     * @return $this
     */
    public function whereTerms(string $field, array $values)
    {
        $this->where[] = [
            'query_type' => QueryTypeConst::TERMS_QUERY,
            'query'      => [
                'field_name' => $field,
                'terms'      => $values
            ]
        ];
        return $this;
    }

    /**
     * 添加范围查询条件
     * @param string $field 字段名
     * @param mixed $from 起始值
     * @param mixed $to 结束值
     * @param bool $includeLower 是否包含下界
     * @param bool $includeUpper 是否包含上界
     * @return $this
     */
    public function whereRange(string $field, $from, $to, bool $includeLower = true, bool $includeUpper = true)
    {
        $this->where[] = [
            'query_type' => QueryTypeConst::RANGE_QUERY,
            'query'      => [
                'field_name'    => $field,
                'range_from'    => $from,
                'include_lower' => $includeLower,
                'range_to'      => $to,
                'include_upper' => $includeUpper
            ]
        ];
        return $this;
    }

    /**
     * 添加前缀查询条件
     * @param string $field 字段名
     * @param string $prefix 前缀
     * @return $this
     */
    public function wherePrefix(string $field, string $prefix)
    {
        $this->where[] = [
            'query_type' => QueryTypeConst::PREFIX_QUERY,
            'query'      => [
                'field_name' => $field,
                'prefix'     => $prefix
            ]
        ];
        return $this;
    }

    /**
     * 添加通配符查询条件
     * @param string $field 字段名
     * @param string $value 通配符值，例如：table*name
     * @return $this
     */
    public function whereWildcard(string $field, string $value)
    {
        $this->where[] = [
            'query_type' => QueryTypeConst::WILDCARD_QUERY,
            'query'      => [
                'field_name' => $field,
                'value'      => $value
            ]
        ];
        return $this;
    }

    /**
     * 添加匹配查询条件（全文检索）
     * @param string $field 字段名
     * @param string $text 检索文本
     * @param int $minimumShouldMatch 最小匹配数
     * @param string $operator and/or
     * @return $this
     */
    public function whereMatch(string $field, string $text, int $minimumShouldMatch = 1, string $operator = 'or')
    {
        $this->where[] = [
            'query_type' => QueryTypeConst::MATCH_QUERY,
            'query'      => [
                'field_name'           => $field,
                'text'                 => $text,
                'minimum_should_match' => $minimumShouldMatch,
                'operator'             => $operator
            ]
        ];
        return $this;
    }

    /**
     * 添加短语匹配查询条件
     * @param string $field 字段名
     * @param string $text 检索短语
     * @return $this
     */
    public function whereMatchPhrase(string $field, string $text)
    {
        $this->where[] = [
            'query_type' => QueryTypeConst::MATCH_PHRASE_QUERY,
            'query'      => [
                'field_name' => $field,
                'text'       => $text
            ]
        ];
        return $this;
    }

    /**
     * 添加嵌套查询条件
     * @param string $path 嵌套路径
     * @param callable $callback 回调函数
     * @return $this
     */
    public function whereNested(string $path, callable $callback)
    {
        $nestedChain = new self();
        call_user_func($callback, $nestedChain);

        $this->where[] = [
            'query_type' => QueryTypeConst::NESTED_QUERY,
            'query'      => [
                'path'       => $path,
                'query'      => [
                    'query_type' => QueryTypeConst::BOOL_QUERY,
                    'query'      => [
                        'must_queries' => $nestedChain->where
                    ]
                ],
                'score_mode' => ScoreModeConst::SCORE_MODE_NONE
            ]
        ];

        return $this;
    }

    /**
     * 添加布尔查询条件
     * @param callable $callback 回调函数
     * @param string $type 类型：must, should, must_not
     * @param int $minimumShouldMatch 最小匹配数
     * @return $this
     */
    public function whereBool(callable $callback, string $type = 'must', int $minimumShouldMatch = 1)
    {
        $boolChain = new self();
        call_user_func($callback, $boolChain);

        $query = [
            'query_type' => QueryTypeConst::BOOL_QUERY,
            'query'      => [
                $type . '_queries' => $boolChain->where
            ]
        ];

        if ($type === 'should') {
            $query['query']['minimum_should_match'] = $minimumShouldMatch;
        }

        $this->where[] = $query;

        return $this;
    }

    /**
     * 添加地理位置距离查询
     * @param string $field 字段名
     * @param float $lat 纬度
     * @param float $lon 经度
     * @param float $distance 距离，单位米
     * @return $this
     */
    public function whereGeoDistance(string $field, float $lat, float $lon, float $distance)
    {
        $this->where[] = [
            'query_type' => QueryTypeConst::GEO_DISTANCE_QUERY,
            'query'      => [
                'field_name'   => $field,
                'center_point' => "{$lat},{$lon}",
                'distance'     => $distance
            ]
        ];
        return $this;
    }

    /**
     * 添加地理位置矩形查询
     * @param string $field 字段名
     * @param float $topLeftLat 左上角纬度
     * @param float $topLeftLon 左上角经度
     * @param float $bottomRightLat 右下角纬度
     * @param float $bottomRightLon 右下角经度
     * @return $this
     */
    public function whereGeoBoundingBox(string $field, float $topLeftLat, float $topLeftLon, float $bottomRightLat, float $bottomRightLon)
    {
        $this->where[] = [
            'query_type' => QueryTypeConst::GEO_BOUNDING_BOX_QUERY,
            'query'      => [
                'field_name'   => $field,
                'top_left'     => "{$topLeftLat},{$topLeftLon}",
                'bottom_right' => "{$bottomRightLat},{$bottomRightLon}"
            ]
        ];
        return $this;
    }

    /**
     * 添加地理位置多边形查询
     * @param string $field 字段名
     * @param array $points 多边形顶点数组，每个元素为 [lat, lon]
     * @return $this
     */
    public function whereGeoPolygon(string $field, array $points)
    {
        $pointsStr = [];
        foreach ($points as $point) {
            $pointsStr[] = "{$point[0]},{$point[1]}";
        }

        $this->where[] = [
            'query_type' => QueryTypeConst::GEO_POLYGON_QUERY,
            'query'      => [
                'field_name' => $field,
                'points'     => $pointsStr
            ]
        ];
        return $this;
    }

    /**
     * 添加存在性查询（字段是否存在）
     * @param string $field 字段名
     * @return $this
     */
    public function whereExists(string $field)
    {
        $this->where[] = [
            'query_type' => QueryTypeConst::EXISTS_QUERY,
            'query'      => [
                'field_name' => $field
            ]
        ];
        return $this;
    }

    /**
     * 重置查询条件
     * @return $this
     */
    public function reset()
    {
        $this->where        = [];
        $this->sort         = [];
        $this->offset       = 0;
        $this->limit        = 20;
        $this->nextToken    = '';
        $this->aggregations = [];
        $this->groupBys     = [];
        $this->highlight    = [];
        $this->parallelScan = [];
        return $this;
    }

    /**
     * 设置SQL查询
     * @param string $sql SQL语句
     * @return $this
     */
    public function sql(string $sql)
    {
        $this->sqlQuery = $sql;
        return $this;
    }

    /**
     * 添加聚合查询
     * @param string $name 聚合名称
     * @param string $type 聚合类型
     * @param array $params 聚合参数
     * @return $this
     */
    public function aggregate(string $name, string $type, array $params)
    {
        $this->aggregations[$name] = [
            'agg_type'  => $type,
            'agg_param' => $params
        ];
        return $this;
    }

    /**
     * 添加分组查询
     * @param string $name 分组名称
     * @param string $type 分组类型
     * @param array $params 分组参数
     * @return $this
     */
    public function groupBy(string $name, string $type, array $params)
    {
        $this->groupBys[$name] = [
            'type' => $type,
            'body' => $params
        ];
        return $this;
    }

    /**
     * 添加字段值分组
     * @param string $name 分组名称
     * @param string $field 字段名
     * @param int $size 返回的分组数量
     * @param array $subAggs 子聚合
     * @param array $subGroupBys 子分组
     * @param int|null $minDocCount 最小文档数
     * @return $this
     */
    public function groupByField(string $name, string $field, int $size = 10, array $subAggs = [], array $subGroupBys = [], int $minDocCount = null)
    {
        $params = [
            'field_name' => $field,
            'size'       => $size
        ];

        if ($minDocCount !== null) {
            $params['min_doc_count'] = $minDocCount;
        }

        if (!empty($subAggs)) {
            $params['sub_aggs']['aggs'] = [$subAggs];
        }

        if (!empty($subGroupBys)) {
            $params['sub_group_bys'] = [
                'group_bys' => $subGroupBys
            ];
        }

        return $this->groupBy($name, GroupByTypeConst::GROUP_BY_FIELD, $params);
    }

    /**
     * 添加范围分组
     * @param string $name 分组名称
     * @param string $field 字段名
     * @param array $ranges 范围数组，每个元素为 ['from' => value, 'to' => value]
     * @param array $subAggs 子聚合
     * @param array $subGroupBys 子分组
     * @return $this
     */
    public function groupByRange(string $name, string $field, array $ranges, array $subAggs = [], array $subGroupBys = [])
    {
        $params = [
            'field_name' => $field,
            'ranges'     => $ranges
        ];

        if (!empty($subAggs)) {
            $params['sub_aggs'] = $subAggs;
        }

        if (!empty($subGroupBys)) {
            $params['sub_group_bys'] = $subGroupBys;
        }

        return $this->groupBy($name, GroupByTypeConst::GROUP_BY_RANGE, $params);
    }

    /**
     * 添加过滤器分组
     * @param string $name 分组名称
     * @param array $filters 过滤器数组，每个元素为 ['query' => [...]]
     * @param array $subAggs 子聚合
     * @param array $subGroupBys 子分组
     * @return $this
     */
    public function groupByFilter(string $name, array $filters, array $subAggs = [], array $subGroupBys = [])
    {
        $params = [
            'filters' => $filters
        ];

        if (!empty($subAggs)) {
            $params['sub_aggs'] = $subAggs;
        }

        if (!empty($subGroupBys)) {
            $params['sub_group_bys'] = $subGroupBys;
        }

        return $this->groupBy($name, GroupByTypeConst::GROUP_BY_FILTER, $params);
    }

    /**
     * 添加地理位置距离分组
     * @param string $name 分组名称
     * @param string $field 字段名
     * @param float $lat 纬度
     * @param float $lon 经度
     * @param array $ranges 距离范围数组，每个元素为 ['from' => value, 'to' => value]
     * @param array $subAggs 子聚合
     * @param array $subGroupBys 子分组
     * @return $this
     */
    public function groupByGeoDistance(string $name, string $field, float $lat, float $lon, array $ranges, array $subAggs = [], array $subGroupBys = [])
    {
        $params = [
            'field_name' => $field,
            'origin'     => [
                'lat' => $lat,
                'lon' => $lon
            ],
            'ranges'     => $ranges
        ];

        if (!empty($subAggs)) {
            $params['sub_aggs'] = $subAggs;
        }

        if (!empty($subGroupBys)) {
            $params['sub_group_bys'] = $subGroupBys;
        }

        return $this->groupBy($name, GroupByTypeConst::GROUP_BY_GEO_DISTANCE, $params);
    }

    /**
     * 添加直方图分组
     * @param string $name 分组名称
     * @param string $field 字段名
     * @param float $interval 间隔
     * @param float $minDocCount 最小文档数
     * @param array $subAggs 子聚合
     * @param array $subGroupBys 子分组
     * @return $this
     */
    public function groupByHistogram(string $name, string $field, float $interval, float $minDocCount = 0, array $subAggs = [], array $subGroupBys = [])
    {
        $params = [
            'field_name'    => $field,
            'interval'      => $interval,
            'min_doc_count' => $minDocCount
        ];

        if (!empty($subAggs)) {
            $params['sub_aggs'] = $subAggs;
        }

        if (!empty($subGroupBys)) {
            $params['sub_group_bys'] = $subGroupBys;
        }

        return $this->groupBy($name, GroupByTypeConst::GROUP_BY_HISTOGRAM, $params);
    }

    /**
     * 创建子聚合
     * @param string $type 聚合类型
     * @param array $params 聚合参数
     * @return array
     */
    public function subAggregation(string $type, array $params)
    {
        return [
            'type'   => $type,
            'params' => $params
        ];
    }

    /**
     * 创建子分组
     * @param string $type 分组类型
     * @param array $params 分组参数
     * @return array
     */
    public function subGroupBy(string $type, array $params)
    {
        return [
            'type'   => $type,
            'params' => $params
        ];
    }

    /**
     * 设置高亮
     * @param array $fields 高亮字段
     * @param array $params 高亮参数
     * @return $this
     */
    public function highlight(array $fields, array $params = [])
    {
        $this->highlight = [
            'field_highlight_params' => $fields,
            'highlight_parameters'   => $params
        ];
        return $this;
    }

    /**
     * 添加批量写入数据
     * @param array $data 数据数组
     * @return $this
     */
    public function addBatchData(array $data)
    {
        $this->batchWriteData[] = $data;
        return $this;
    }

    /**
     * 批量写入数据
     * @return mixed
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function batchWrite()
    {
        if (empty($this->batchWriteData)) {
            return false;
        }

        return $this->OtsCreateClient()->batchWriteRow($this->batchWriteData);
    }

    /**
     * 执行SQL查询
     * @return array
     * @throws ErrException
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function sqlQuery()
    {
        if (empty($this->sqlQuery)) {
            throw new ErrException(Code::PARAMS_ERROR, 'SQL查询语句不能为空');
        }

        $request = [
            'query' => $this->sqlQuery
        ];

        $response = $this->OtsCreateClient()->sqlQuery($request);

        // 处理SQL查询结果
        $sqlRows = $response['sql_rows'];
        $result  = [];
        for ($i = 0; $i < $sqlRows->rowCount; $i++) {
            $row = [];
            for ($j = 0; $j < $sqlRows->columnCount; $j++) {
                $columnName       = $sqlRows->getTableMeta()->getSchemaByIndex($j)['name'];
                $row[$columnName] = $sqlRows->get($j, $i);
            }
            $result[] = $row;
        }

        return $result;
    }

    /**
     * 构建查询请求
     * @return array
     */
    protected function buildRequest()
    {
        $request = [
            'table_name'     => $this->tableName,
            'index_name'     => $this->indexName,
            'search_query'   => [
                'offset'          => $this->offset,
                'limit'           => $this->limit,
                'get_total_count' => true,
            ],
            'columns_to_get' => [
                'return_type'  => ColumnReturnTypeConst::RETURN_SPECIFIED,
                'return_names' => $this->returnNames
            ]
        ];

        // 设置排序
        if (!empty($this->sort) && empty($this->nextToken)) {
            $request['search_query']['sort'] = $this->sort;
        }

        // 设置分页token
        if (!empty($this->nextToken)) {
            $request['search_query']['token'] = base64_decode($this->nextToken);
        }

        // 设置查询条件
        if (empty($this->where)) {
            $request['search_query']['query'] = ['query_type' => QueryTypeConst::MATCH_ALL_QUERY];
        } else {
            $request['search_query']['query'] = [
                'query_type' => QueryTypeConst::BOOL_QUERY,
                'query'      => [
                    'filter_queries' => $this->where
                ]
            ];
        }

        // 设置聚合查询
        if (!empty($this->aggregations)) {
            $aggs = [];
            foreach ($this->aggregations as $name => $agg) {
                $aggs[] = [
                    'name' => $name,
                    'type' => $agg['agg_type'],
                    'body' => $agg['agg_param']
                ];
            }
            $request['search_query']['aggs'] = [
                'aggs' => $aggs
            ];
        }

        // 设置分组查询
        if (!empty($this->groupBys)) {
            $groupBys = [];
            foreach ($this->groupBys as $name => $groupBy) {
                $groupBys[] = [
                    'name' => $name,
                    'type' => $groupBy['type'],
                    'body' => $groupBy['body']
                ];
            }
            $request['search_query']['group_bys'] = [
                'group_bys' => $groupBys
            ];
        }

        // 设置高亮
        if (!empty($this->highlight)) {
            $request['search_query']['highlight'] = $this->highlight;
        }

        return $request;
    }

    /**
     * 执行查询
     * @return array
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function get()
    {
        $request = $this->buildRequest();

        $resp = $this->OtsCreateClient()->search($request);

        if (!empty($resp['next_token'])) {
            $resp['next_token'] = base64_encode($resp['next_token']);
        }

        if (!empty($resp['rows'])) {
            $resp['rows'] = $this->otsStructureToJson($resp['rows']);
        }

        return $resp;
    }

    /**
     * 获取单条记录
     * @param string $id
     * @return array
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function find(string $id)
    {
        $this->whereTerm($this->primaryKey, $id);
        $this->offsetLimit(0, 1);

        $resp = $this->get();
        $data = [];

        if (!empty($resp['rows'])) {
            $data = $resp['rows'][0] ?? [];
        }

        return $data;
    }

    /**
     * 获取单条记录（通过主键）
     * @param string $id
     * @return array
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function getRow(string $id)
    {
        $request  = [
            'table_name'     => $this->tableName,
            'primary_key'    => [
                [$this->primaryKey, $id]
            ],
            'columns_to_get' => $this->returnNames
        ];
        $response = $this->OtsCreateClient()->getRow($request);

        if (isset($response['primary_key']) && isset($response['attribute_columns'])) {
            $row = [];
            // 处理主键
            foreach ($response['primary_key'] as $item) {
                $row[$item[0]] = $item[1];
            }
            // 处理属性列
            foreach ($response['attribute_columns'] as $item) {
                $row[$item[0]] = $item[1];
            }
            return $row;
        }

        return [];
    }

    /**
     * 插入单条记录
     * @param array $data
     * @return mixed
     * @throws ErrException
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function insert(array $data)
    {
        if (!isset($data[$this->primaryKey])) {
            throw new ErrException(Code::PARAMS_ERROR, '主键不能为空');
        }
        $request = [
            'table_name'        => $this->tableName,
            'condition'         => RowExistenceExpectationConst::CONST_IGNORE,
            'primary_key'       => [
                [$this->primaryKey, $data[$this->primaryKey]]
            ],
            'attribute_columns' => $this->processData($data)
        ];

        return $this->OtsCreateClient()->putRow($request);
    }

    /**
     * 更新单条记录
     * @param string $id
     * @param array $data
     * @return mixed
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function update(string $id, array $data)
    {
        $request = [
            'table_name'                  => $this->tableName,
            'condition'                   => RowExistenceExpectationConst::CONST_IGNORE,
            'primary_key'                 => [
                [$this->primaryKey, $id]
            ],
            'update_of_attribute_columns' => [
                'PUT' => $this->processData($data)
            ]
        ];
        return $this->OtsCreateClient()->updateRow($request);
    }

    /**
     * 删除单条记录
     * @param string $id
     * @return mixed
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function delete(string $id)
    {
        $request = [
            'table_name'  => $this->tableName,
            'condition'   => RowExistenceExpectationConst::CONST_IGNORE,
            'primary_key' => [
                [$this->primaryKey, $id]
            ]
        ];

        return $this->OtsCreateClient()->deleteRow($request);
    }

    /**
     * 重建索引
     * @param string $sourceIndexName
     * @return mixed
     * @throws ErrException
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function reindex(string $sourceIndexName = '')
    {
        if (empty($this->indexSchema)) {
            throw new ErrException(Code::PARAMS_ERROR, '请设置索引架构');
        }
        $request = [
            'table_name' => $this->tableName,
            'index_name' => $this->indexName,
            'schema'     => $this->indexSchema
        ];

        if ($sourceIndexName) {
            $request['index_name']        = $this->indexName . '_reindex';
            $request['source_index_name'] = $sourceIndexName;
        }

        return $this->OtsCreateClient()->createSearchIndex($request);
    }

    /**
     * 创建表
     * @param array $primaryKeySchema 主键结构
     * @param array $tableOptions 表选项
     * @return mixed
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function createTable(array $primaryKeySchema = [], array $definedColumn = [], array $tableOptions = [])
    {
        if (empty($primaryKeySchema)) {
            $primaryKeySchema = [
                [$this->primaryKey, PrimaryKeyTypeConst::CONST_STRING]
            ];
        }

        if (empty($tableOptions)) {
            $tableOptions = [
                'time_to_live'                  => -1,    // 数据生命周期, -1表示永久，单位秒
                'max_versions'                  => 1,     // 最大数据版本
                'deviation_cell_version_in_sec' => 86400  // 数据有效版本偏差，单位秒
            ];
        }

        $request = [
            'table_meta'          => [
                'table_name'         => $this->tableName,
                'primary_key_schema' => $primaryKeySchema
            ],
            'reserved_throughput' => [
                'capacity_unit' => [
                    'read'  => 0, // 预留读写吞吐量设置为：0个读CU，和0个写CU
                    'write' => 0
                ]
            ],
            'table_options'       => $tableOptions
        ];


        if ($definedColumn) {
            $request['table_meta']['defined_column'] = $definedColumn;
        }

        return $this->OtsCreateClient()->createTable($request);
    }

    /**
     * 删除表
     * @return mixed
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function dropTable()
    {
        $request = [
            'table_name' => $this->tableName
        ];

        return $this->OtsCreateClient()->deleteTable($request);
    }

    /**
     * 列出所有表
     * @return array
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function listTables()
    {
        return $this->OtsCreateClient()->listTable([]);
    }

    /**
     * 描述表结构
     * @return mixed
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function describeTable()
    {
        $request = [
            'table_name' => $this->tableName
        ];

        return $this->OtsCreateClient()->describeTable($request);
    }

    /**
     * 查询多元索引描述信息
     * @return mixed
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function describeSearchIndex()
    {
        $request = [
            'table_name' => $this->tableName,
            'index_name' => $this->indexName
        ];

        return $this->OtsCreateClient()->describeSearchIndex($request);
    }

    /**
     * 更新表选项
     * @param array $tableOptions
     * @return mixed
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function updateTable(array $tableOptions)
    {
        $request = [
            'table_name'    => $this->tableName,
            'table_options' => $tableOptions
        ];

        return $this->OtsCreateClient()->updateTable($request);
    }

    /**
     * 列出所有索引
     * @return mixed
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function listSearchIndex()
    {
        $request = [
            'table_name' => $this->tableName
        ];

        return $this->OtsCreateClient()->listSearchIndex($request);
    }

    /**
     * 删除索引
     * @return mixed
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function deleteSearchIndex()
    {
        $request = [
            'table_name' => $this->tableName,
            'index_name' => $this->indexName
        ];

        return $this->OtsCreateClient()->deleteSearchIndex($request);
    }

    /**
     * 创建索引
     * @param array $schema 索引结构
     * @return mixed
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function createSearchIndex(array $schema)
    {
        $request = [
            'table_name' => $this->tableName,
            'index_name' => $this->indexName,
            'schema'     => $schema
        ];

        return $this->OtsCreateClient()->createSearchIndex($request);
    }

    /**
     * 批量获取行
     * @param array $ids 主键ID数组
     * @return array
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function batchGetRow(array $ids)
    {
        $rows = [];
        foreach ($ids as $id) {
            $rows[] = [
                'primary_key' => [[$this->primaryKey, $id]]
            ];
        }

        $request = [
            'tables' => [
                [
                    'table_name'     => $this->tableName,
                    'rows'           => $rows,
                    'columns_to_get' => $this->returnNames
                ]
            ]
        ];

        $response = $this->OtsCreateClient()->batchGetRow($request);

        $result = [];
        if (isset($response['tables']) && !empty($response['tables'])) {
            $formattedRows = [];
            foreach ($response['tables'][0]['rows'] as $row) {
                if ($row['is_ok']) {
                    $formattedRows[] = [
                        'primary_key'       => $row['primary_key'],
                        'attribute_columns' => $row['attribute_columns']
                    ];
                }
            }
            $result = $this->formatRowsData($formattedRows);
        }
        return $result;
    }

    /**
     * 范围查询
     * @param array $startPrimaryKey 起始主键
     * @param array $endPrimaryKey 结束主键
     * @param string $direction 方向：FORWARD/BACKWARD
     * @param int $limit 限制数量
     * @return array
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function getRange(array $startPrimaryKey, array $endPrimaryKey, string $direction = 'FORWARD', int $limit = 100)
    {
        $request = [
            'table_name'                  => $this->tableName,
            'direction'                   => $direction,
            'inclusive_start_primary_key' => $startPrimaryKey,
            'exclusive_end_primary_key'   => $endPrimaryKey,
            'limit'                       => $limit,
            'columns_to_get'              => $this->returnNames
        ];

        $response = $this->OtsCreateClient()->getRange($request);
        return $this->formatRowsData($response['rows'] ?? []);
    }

    /**
     * 添加函数评分查询
     * @param callable $callback 回调函数
     * @param array $params 函数参数
     * @return $this
     */
    public function whereFunctionScore(callable $callback, array $params = [])
    {
        $functionScoreChain = new self();
        call_user_func($callback, $functionScoreChain);

        $this->where[] = [
            'query_type' => QueryTypeConst::FUNCTION_SCORE_QUERY,
            'query'      => [
                'query'     => [
                    'query_type' => QueryTypeConst::BOOL_QUERY,
                    'query'      => [
                        'filter_queries' => $functionScoreChain->where
                    ]
                ],
                'functions' => $params
            ]
        ];

        return $this;
    }

    /**
     * 设置并行扫描参数
     * @param int $sessionId 会话ID
     * @param int $currentParallelId 当前并行ID
     * @param int $maxParallel 最大并行数
     * @return $this
     */
    public function parallelScan(int $sessionId, int $currentParallelId, int $maxParallel)
    {
        $this->parallelScan = [
            'session_id'          => $sessionId,
            'current_parallel_id' => $currentParallelId,
            'max_parallel'        => $maxParallel
        ];

        return $this;
    }

    /**
     * 执行并行扫描
     * @return mixed
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function scan()
    {
        $request = $this->buildRequest();

        if (!empty($this->parallelScan)) {
            $request['search_query']['scan_query'] = $this->parallelScan;
        }

        $resp = $this->OtsCreateClient()->parallelScan($request);

        if (!empty($resp['rows'])) {
            $resp['rows'] = $this->otsStructureToJson($resp['rows']);
        }

        return $resp;
    }

    /**
     * 将OTS返回的数据结构转换为JSON格式
     * @param array $rows OTS返回的行数据
     * @return array 转换后的数据
     */
    public function otsStructureToJson($rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $item = [];
            // 处理主键
            foreach ($row['primary_key'] as $pk) {
                $item[$pk[0]] = $pk[1];
            }
            // 处理属性列
            foreach ($row['attribute_columns'] as $attr) {
                $item[$attr[0]] = $attr[1];
            }
            $result[] = $item;
        }
        return $result;
    }

    /**
     * 添加计数聚合
     * @param string $name 聚合名称
     * @param string $field 字段名
     * @param array $subAggs 子聚合
     * @return $this
     */
    public function count(string $name, string $field, array $subAggs = [])
    {
        $params = [
            'field_name' => $field,
            'missing'    => 0,
        ];

        if (!empty($subAggs)) {
            $params['sub_aggs'] = $subAggs;
        }

        return $this->aggregate($name, AggregationTypeConst::AGG_COUNT, $params);
    }


    /**
     * 添加去重计数聚合
     * @param string $name 聚合名称
     * @param string $field 字段名
     * @param array $subAggs 子聚合
     * @return $this
     */
    public function distinctCount(string $name, string $field, array $subAggs = [])
    {
        $params = [
            'field_name' => $field,
        ];

        if (!empty($subAggs)) {
            $params['sub_aggs'] = $subAggs;
        }

        return $this->aggregate($name, AggregationTypeConst::AGG_DISTINCT_COUNT, $params);
    }

    /**
     * 添加百分位聚合
     * @param string $name 聚合名称
     * @param string $field 字段名
     * @param array $percentiles 百分位数组，如 [50, 95, 99]
     * @param array $subAggs 子聚合
     * @return $this
     */
    public function percentiles(string $name, string $field, array $percentiles = [50, 95, 99], array $subAggs = [])
    {
        $params = [
            'field_name'  => $field,
            'percentiles' => $percentiles
        ];

        if (!empty($subAggs)) {
            $params['sub_aggs'] = $subAggs;
        }

        return $this->aggregate($name, AggregationTypeConst::AGG_PERCENTILES, $params);
    }

    /**
     * 添加获取Top行聚合
     * @param string $name 聚合名称
     * @param int $count 获取行数
     * @param array $sort 排序条件
     * @param array $subAggs 子聚合
     * @return $this
     */
    public function topRows(string $name, int $count = 10, array $sort = [], array $subAggs = [])
    {
        $params = [
            'limit' => $count
        ];

        if (!empty($sort)) {
            $params['sort'] = $sort;
        }

        if (!empty($subAggs)) {
            $params['sub_aggs'] = $subAggs;
        }

        return $this->aggregate($name, AggregationTypeConst::AGG_TOP_ROWS, $params);
    }

    /**
     * 添加最小值聚合
     * @param string $name 聚合名称
     * @param string $field 字段名
     * @param array $subAggs 子聚合
     * @return $this
     */
    public function min(string $name, string $field, array $subAggs = [])
    {
        $params = [
            'field_name' => $field
        ];

        if (!empty($subAggs)) {
            $params['sub_aggs'] = $subAggs;
        }

        return $this->aggregate($name, AggregationTypeConst::AGG_MIN, $params);
    }

    /**
     * 添加最大值聚合
     * @param string $name 聚合名称
     * @param string $field 字段名
     * @param array $subAggs 子聚合
     * @return $this
     */
    public function max(string $name, string $field, array $subAggs = [])
    {
        $params = [
            'field_name' => $field
        ];

        if (!empty($subAggs)) {
            $params['sub_aggs'] = $subAggs;
        }

        return $this->aggregate($name, AggregationTypeConst::AGG_MAX, $params);
    }

    /**
     * 添加求和聚合
     * @param string $name 聚合名称
     * @param string $field 字段名
     * @param array $subAggs 子聚合
     * @return $this
     */
    public function sum(string $name, string $field, array $subAggs = [])
    {
        $params = [
            'field_name' => $field
        ];

        if (!empty($subAggs)) {
            $params['sub_aggs'] = $subAggs;
        }

        return $this->aggregate($name, AggregationTypeConst::AGG_SUM, $params);
    }

    /**
     * 添加平均值聚合
     * @param string $name 聚合名称
     * @param string $field 字段名
     * @param array $subAggs 子聚合
     * @return $this
     */
    public function avg(string $name, string $field, array $subAggs = [])
    {
        $params = [
            'field_name' => $field
        ];

        if (!empty($subAggs)) {
            $params['sub_aggs'] = $subAggs;
        }

        return $this->aggregate($name, AggregationTypeConst::AGG_AVG, $params);
    }

    /**
     * 获取第一条记录
     * @return array|null
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function first()
    {
        $this->offsetLimit(0, 1);
        $resp = $this->get();

        if (!empty($resp['rows'])) {
            return $resp['rows'][0];
        }

        return null;
    }

    /**
     * 判断记录是否存在
     * @return bool
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function exists()
    {
        return $this->count() > 0;
    }

    /**
     * 获取原始客户端
     * @return \Aliyun\OTS\OTSClient
     */
    public function getClient()
    {
        return $this->OtsCreateClient();
    }

    /**
     * 格式化OTS返回的行数据
     * @param array $rows OTS返回的行数据
     * @return array 格式化后的数据
     */
    protected function formatRowsData(array $rows)
    {
        $result = [];
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $item = [];
                // 处理主键
                foreach ($row['primary_key'] as $pk) {
                    $item[$pk[0]] = $pk[1];
                }
                // 处理属性列
                foreach ($row['attribute_columns'] as $attr) {
                    $item[$attr[0]] = $attr[1];
                }
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * 处理数据，将数组值转换为JSON字符串
     * @param array $data 原始数据
     * @return array 处理后的数据
     */
    protected function processData(array $data)
    {
        $processed = [];
        foreach ($data as $key => $value) {
            if ($key !== $this->primaryKey) {
                if (is_array($value)) {
                    $processed[] = [$key, json_encode($value, JSON_UNESCAPED_UNICODE)];
                } else {
                    $processed[] = [$key, $value];
                }
            }
        }
        return $processed;
    }

    /**
     * 批量更新记录
     * @param array $data 格式为 ['id' => ['field' => 'value', ...], ...]
     * @return mixed 更新结果
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function batchUpdate(array $data)
    {
        $tables = $rows = [];
        foreach ($data as $id => $fields) {
            $attributes = [];
            foreach ($fields as $key => $value) {
                if ($key !== $this->primaryKey) {
                    if (is_array($value)) {
                        $attributes[] = [$key, json_encode($value, JSON_UNESCAPED_UNICODE)];
                    } else {
                        $attributes[] = [$key, $value];
                    }
                }
            }

            $rows[] = [
                'operation_type'              => OperationTypeConst::CONST_UPDATE,
                'condition'                   => RowExistenceExpectationConst::CONST_IGNORE,
                'primary_key'                 => [
                    [$this->primaryKey, $id, PrimaryKeyTypeConst::CONST_STRING]
                ],
                'update_of_attribute_columns' => [
                    OperationTypeConst::CONST_PUT => $attributes
                ]
            ];
        }

        $tables[] = [
            'table_name' => $this->tableName,
            'rows'       => $rows
        ];

        $request = ['tables' => $tables];
        return $this->OtsCreateClient()->batchWriteRow($request);
    }


    /**
     * 开始事务
     * @return string 事务ID
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function startTransaction()
    {
        $request = [
            'table_name' => $this->tableName
        ];

        $response = $this->OtsCreateClient()->startLocalTransaction($request);
        return $response['transaction_id'];
    }

    /**
     * 提交事务
     * @param string $transactionId 事务ID
     * @return mixed
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function commitTransaction(string $transactionId)
    {
        $request = [
            'transaction_id' => $transactionId
        ];

        return $this->OtsCreateClient()->commitTransaction($request);
    }

    /**
     * 中止事务
     * @param string $transactionId 事务ID
     * @return mixed
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function abortTransaction(string $transactionId)
    {
        $request = [
            'transaction_id' => $transactionId
        ];

        return $this->OtsCreateClient()->abortTransaction($request);
    }

    /**
     * 批量删除记录
     * @param array $ids 主键ID数组
     * @return mixed
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function batchDelete(array $ids)
    {
        $rows = [];
        foreach ($ids as $id) {
            $rows[] = [
                'primary_key' => [
                    [$this->primaryKey, $id]
                ]
            ];
        }

        $request = [
            'tables' => [
                [
                    'table_name' => $this->tableName,
                    'rows'       => $rows
                ]
            ]
        ];

        return $this->OtsCreateClient()->batchWriteRow($request);
    }

    /**
     * 获取分组统计结果
     * @return mixed|null
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function getGroupByResult()
    {
        $request = $this->buildRequest();
        $resp    = $this->OtsCreateClient()->search($request);

        if (!empty($resp['group_bys']) && !empty($resp['group_bys']['group_by_results'])) {
            foreach ($resp['group_bys']['group_by_results'] as $groupByResult) {
                if (isset($groupByResult['group_by_result'])) {
                    return $groupByResult['group_by_result']['items'];
                }
            }
        }

        return null;
    }

    /**
     * @return mixed|null
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function getAggResult()
    {
        $request = $this->buildRequest();
        $resp    = $this->OtsCreateClient()->search($request);
        if (!empty($resp['aggs']) && !empty($resp['aggs']['agg_results'])) {
            return $resp['aggs']['agg_results'];
        }
        return null;
    }
}