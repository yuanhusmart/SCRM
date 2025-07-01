<?php
namespace common\helpers;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Excel 表格助手
 * Class ExcelHelper
 * @package common\helpers
 */
class ExcelHelper
{

    /**
     * 上下文
     * @var Spreadsheet
     */
    public $spreadsheet;

    /**
     * 当前活跃的表格页
     * @var Worksheet
     */
    public $activeSheet;

    /**
     * 当前活跃的表格页码
     * @var int
     */
    public $worksheet = 0;

    /**
     * 每一页表格最大的行数
     * @var int
     */
    public $maxLine = 1048576;

    /**
     * 当前表格页的当前行数
     * @var int
     */
    public $line = 1;

    /**
     * 表头
     * @var string[]
     */
    public $header = [];

    /**
     * 文件保存路径
     * @var string
     */
    public $filepath = '';

    /**
     * 实例化
     * ExcelHelper constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->createSheet();
        $this->activeSheet = $this->spreadsheet->setActiveSheetIndex($this->worksheet);
    }

    /**
     * 设置表头
     * @param array $items
     */
    public function setHeader($items)
    {
        $this->header = $items;
    }

    /**
     * 写入一行数据
     * @param array $items
     * @throws \Exception
     */
    public function writeLine($items)
    {
        if($this->line > $this->maxLine){
            $this->line = 1;
            $this->worksheet++;
            $this->activeSheet = $this->spreadsheet->setActiveSheetIndex($this->worksheet);
        }
        if($this->line == 1){
            $this->writeItems($this->header);
        }
        $this->writeItems($items);
    }

    /**
     * 写入一行数据
     * @param array $items
     */
    private function writeItems($items)
    {
        foreach($items as $k => $item){
            $this->activeSheet->setCellValueExplicit(StringHelper::intToLetter($k + 1).$this->line, $item, DataType::TYPE_STRING);
        }
        $this->line++;
    }

    /**
     * 设置文件保存的路径
     * @param string $filepath
     */
    public function setFilepath($filepath)
    {
        $this->filepath = $filepath;
    }

    /**
     * @param string $filename
     */
    public function responseHeader($filename)
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename='.urlencode($filename));
        header('Cache-Control: max-age=0');
    }

    /**
     * @throws \Exception
     */
    public function responseBody()
    {
        $this->save('php://output');
        exit();
    }

    /**
     * 保存文件
     * @param string $filepath
     * @throws \Exception
     */
    public function save($filepath = '')
    {
        if(!$filepath){
            $filepath = $this->filepath;
        }
        $this->spreadsheet->setActiveSheetIndex(0);
        $xlsx = new Xlsx($this->spreadsheet);
        $xlsx->save($filepath);
    }

}