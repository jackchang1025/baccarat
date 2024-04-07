<?php
namespace App\Baccarat\Dto;

use Mine\Interfaces\MineModelExcel;
use Mine\Annotation\ExcelData;
use Mine\Annotation\ExcelProperty;

/**
 * 投注规则Dto （导入导出）
 */
#[ExcelData]
class BaccaratSimulatedBettingRuleDto implements MineModelExcel
{
    #[ExcelProperty(value: "主键", index: 0)]
    public string $id;

    #[ExcelProperty(value: "创建时间", index: 1)]
    public string $created_at;

    #[ExcelProperty(value: "更新时间", index: 2)]
    public string $updated_at;

    #[ExcelProperty(value: "名称", index: 3)]
    public string $title;

    #[ExcelProperty(value: "规则", index: 4)]
    public string $rule;

    #[ExcelProperty(value: "投注值", index: 5)]
    public string $betting_value;

    #[ExcelProperty(value: "状态 (1正常 2停用)", index: 6)]
    public string $status;

    #[ExcelProperty(value: "排序", index: 7)]
    public string $sort;

    #[ExcelProperty(value: "备注", index: 8)]
    public string $remark;


}