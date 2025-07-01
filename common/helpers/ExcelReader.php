<?php

namespace common\helpers;

use common\errors\Code;
use common\errors\ErrException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

/**
 * Class ExcelReader
 * @package common\helpers
 */
class ExcelReader
{

    public $filepath = '';

    public $labels = [];

    /**
     * 总行数
     * @var int
     */
    public $rowCount = 0;

    /**
     * 总列数
     * @var int
     */
    public $columnCount = 0;

    public $maxColumnCount = -1;

    /**
     * ExcelReader constructor.
     * @param string $filepath
     */
    public function __construct($filepath = '')
    {
        $this->filepath = $filepath;
    }

    /**
     * @param string $value
     */
    public function setFilepath($value)
    {
        $this->filepath = $value;
    }

    /**
     * @param array $values
     */
    public function setLabels($values)
    {
        $this->labels = $values;
    }

    /**
     * @param int $value
     */
    public function setMaxColumnCount($value)
    {
        $this->maxColumnCount = $value;
    }

    /**
     * @param callable $callback
     * @throws \Exception
     */
    public function read($callback)
    {
        $reader_file = ucfirst(pathinfo($this->filepath)['extension']);
        $objRead = IOFactory::createReader($reader_file);
        $spreadsheet       = $objRead->load($this->filepath);
        $worksheet         = $spreadsheet->getActiveSheet();
        $this->rowCount    = $worksheet->getHighestRow();
        $this->columnCount = StringHelper::letterToInt($worksheet->getHighestColumn());
        $fields            = [];
        $datas             = [];
        $success           = 0;
        $column_count      = 0;
        for ($row = 1; $row <= $this->rowCount; $row++) {
            $data = [];
            for ($column = 1; $column <= $this->columnCount; $column++) {
                $value = $worksheet->getCellByColumnAndRow($column, $row)->getValue();
                if ($row == 1) {
                    $field = '';
                    foreach ($this->labels as $k => $label) {
                        if ($value == $label) {
                            $field = $k;
                            break;
                        }
                    }
                    $fields[] = $field;
                } else {
                    if (isset($fields[$column - 1]) && $fields[$column - 1]) {
                        $data[$fields[$column - 1]] = $value;
                    }
                }
            }
            if ($data) {
                $column_count++;
                if ($this->maxColumnCount > 0 && $column_count > $this->maxColumnCount) {
                    throw new ErrException(Code::DATA_ERROR, 'Excel行数超过限制，最大允许' . $this->maxColumnCount . '行');
                }
                $datas[] = $data;
            }
        }
        $this->columnCount = $column_count;
        foreach ($datas as $data) {
            $callback($data, $success);
            $success++;
        }
    }
}