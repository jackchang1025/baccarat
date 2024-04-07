<?php
namespace App\Baccarat\Dto;

use Mine\Interfaces\MineModelExcel;
use Mine\Annotation\ExcelData;
use Mine\Annotation\ExcelProperty;

/**
 * 规则Dto （导入导出）
 */
#[ExcelData]
class BaccaratRuleDto implements MineModelExcel
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

    #[ExcelProperty(value: "备注", index: 5)]
    public string $remark;


}