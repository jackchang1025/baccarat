<?php
namespace App\Baccarat\Dto;

use Mine\Interfaces\MineModelExcel;
use Mine\Annotation\ExcelData;
use Mine\Annotation\ExcelProperty;

/**
 * 投注Dto （导入导出）
 */
#[ExcelData]
class BaccaratSimulatedBettingDto implements MineModelExcel
{
    #[ExcelProperty(value: "主键", index: 0)]
    public string $id;

    #[ExcelProperty(value: "创建时间", index: 1)]
    public string $created_at;

    #[ExcelProperty(value: "更新时间", index: 2)]
    public string $updated_at;

    #[ExcelProperty(value: "名称", index: 3)]
    public string $title;

    #[ExcelProperty(value: "投递序列", index: 4)]
    public string $betting_sequence;

    #[ExcelProperty(value: "状态 (1正常 2停用)", index: 5)]
    public string $status;

    #[ExcelProperty(value: "排序", index: 6)]
    public string $sort;

    #[ExcelProperty(value: "备注", index: 7)]
    public string $remark;


}