<?php
namespace common\helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelExporter
{
    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function exportWithDataAndImages($data, $titles, $outputPath, $imageFields = [])
    {
        // 创建新的Excel对象
        $spreadsheet = new Spreadsheet();

        // 获取工作表对象
        $worksheet = $spreadsheet->getActiveSheet();

        // 设置列标题
        $column = 'A';
        foreach ($titles as $field => $title) {
            $worksheet->setCellValue($column . '1', $title);
            $column++;
        }

        // 插入图片和文本数据
        $row = 2; // 数据起始行
        $tempPaths = [];
        foreach ($data as $rowData) {
            $column = 'A'; // 起始列

            foreach ($rowData as $field => $value) {
                if ($field == 'order_sn') {
                    echo "订单号:" . $value . "  开始导出" . PHP_EOL;
                }
                try {
                    if (in_array($field, $imageFields)) {
                        // 对于包含图片的字段，插入多张图片
                        $imagePaths = $value;

                        foreach ($imagePaths as $index => $imagePath) {
                            $drawing = new Drawing();
                            $drawing->setName('Sample image');
                            $drawing->setDescription('This is a sample image');
                            $drawing->setWorksheet($worksheet);
                            // 将图片插入到单元格内部
                            $cell = $column . $row;


                            if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                                // 如果是远程图片地址，则先下载到临时文件再插入
                                $tempPaths[] = $tempPath = tempnam(sys_get_temp_dir(), 'image');
                                file_put_contents($tempPath, file_get_contents($imagePath));
                                $drawing->setPath($tempPath);

                            } else {
                                // 如果是本地图片地址，则直接插入
                                $drawing->setPath($imagePath);
                            }

                            $drawing->setHeight(100);//图片高
                            $drawing->setCoordinates($cell); // 图片插入的位置
                            // 调整单元格宽高以适应图片
                            $worksheet->getColumnDimension($column)->setAutoSize(true);
                            $worksheet->getRowDimension($row)->setRowHeight($drawing->getHeight());

                            $column++;

                        }
                    } else {
                        // 对于其他文本字段，插入文本数据
                        $worksheet->setCellValue($column . $row, $value);
                    }

                }catch (\Exception $exception) {
                    if (!empty($rowData['order_sn'])) {
                        echo "订单号：" . $rowData['order_sn']."，字段：" . $field . ", 导出错误" . PHP_EOL;
                    }

                    continue;
                }
                $column++;

            }
            if (!empty($rowData['order_sn'])) {
                echo "订单号：" . $rowData['order_sn'] . ", 写入成功" . PHP_EOL;
            }

            // 向下移动一行，准备插入下一组数据
            $row++;
        }

        // 导出Excel文件
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outputPath);

        if (isset($tempPaths)) {
            foreach ($tempPaths as $tempPath) {
                @unlink($tempPath);
            }
        }
    }
}

