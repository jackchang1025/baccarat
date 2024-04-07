<?php
namespace App\Baccarat\Dto;

use Mine\Interfaces\MineModelExcel;
use Mine\Annotation\ExcelData;
use Mine\Annotation\ExcelProperty;

/**
 * 投注日志表Dto （导入导出）
 */
#[ExcelData]
class BaccaratSimulatedBettingLogDto implements MineModelExcel
{
    #[ExcelProperty(value: "主键", index: 0)]
    public string $id;

    #[ExcelProperty(value: "投注id", index: 1)]
    public string $baccarat_simulated_betting_id;

    #[ExcelProperty(value: "期号", index: 2)]
    public string $issue;

    #[ExcelProperty(value: "投注值", index: 3)]
    public string $betting_value;

    #[ExcelProperty(value: "投注结果", index: 4)]
    public string $betting_result;

    #[ExcelProperty(value: "状态 (1正常 2停用)", index: 5)]
    public string $status;

    #[ExcelProperty(value: "备注", index: 6)]
    public string $remark;

    #[ExcelProperty(value: "创建时间", index: 7)]
    public string $created_at;

    #[ExcelProperty(value: "更新时间", index: 8)]
    public string $updated_at;


}