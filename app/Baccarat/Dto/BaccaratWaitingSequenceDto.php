<?php
namespace App\Baccarat\Dto;

use Mine\Interfaces\MineModelExcel;
use Mine\Annotation\ExcelData;
use Mine\Annotation\ExcelProperty;

/**
 * 表注释Dto （导入导出）
 */
#[ExcelData]
class BaccaratWaitingSequenceDto implements MineModelExcel
{
    #[ExcelProperty(value: "主键", index: 0)]
    public string $id;

    #[ExcelProperty(value: "名称", index: 1)]
    public string $title;

    #[ExcelProperty(value: "序列", index: 2)]
    public string $sequence;

    #[ExcelProperty(value: "创建时间", index: 3)]
    public string $created_at;

    #[ExcelProperty(value: "更新时间", index: 4)]
    public string $updated_at;

    #[ExcelProperty(value: "删除时间", index: 5)]
    public string $deleted_at;

    #[ExcelProperty(value: "备注", index: 6)]
    public string $remark;


}