<?php
namespace App\Baccarat\Dto;

use Mine\Interfaces\MineModelExcel;
use Mine\Annotation\ExcelData;
use Mine\Annotation\ExcelProperty;

/**
 * 牌靴Dto （导入导出）
 */
#[ExcelData]
class BaccaratTerraceDeckDto implements MineModelExcel
{
    #[ExcelProperty(value: "主键", index: 0)]
    public string $id;

    #[ExcelProperty(value: "台", index: 1)]
    public string $terrace_id;

    #[ExcelProperty(value: "牌靴编号", index: 2)]
    public string $deck_number;

    #[ExcelProperty(value: "开奖序列", index: 3)]
    public string $lottery_sequence;

    #[ExcelProperty(value: "创建时间", index: 4)]
    public string $created_at;

    #[ExcelProperty(value: "更新时间", index: 5)]
    public string $updated_at;

    #[ExcelProperty(value: "备注", index: 6)]
    public string $remark;


}