<?php
namespace App\Baccarat\Dto;

use Mine\Interfaces\MineModelExcel;
use Mine\Annotation\ExcelData;
use Mine\Annotation\ExcelProperty;

/**
 * 投注日志规则表Dto （导入导出）
 */
#[ExcelData]
class BaccaratBettingRuleDto implements MineModelExcel
{
    #[ExcelProperty(value: "主键", index: 0)]
    public string $id;

    #[ExcelProperty(value: "名称", index: 1)]
    public string $title;

    #[ExcelProperty(value: "规则", index: 2)]
    public string $rule;

    #[ExcelProperty(value: "投注值", index: 3)]
    public string $betting_value;

    #[ExcelProperty(value: "创建时间", index: 4)]
    public string $created_at;

    #[ExcelProperty(value: "更新时间", index: 5)]
    public string $updated_at;


}