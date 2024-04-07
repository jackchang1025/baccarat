<?php

namespace App\Baccarat\Service\Coordinates;

use App\Baccarat\Model\BaccaratLotteryLog;
use Hyperf\Database\Model\Collection;

class CalculateCoordinates
{
    protected string $lastResult = '';
    protected array $startXCoordinates = ['B' => 1, 'P' => 1]; // 记录每个结果序列起始的X坐标
    protected int $x = 1;
    protected int $y = 6;
    protected int $minY = 1;
    protected Collection $data;

    public function calculateCoordinatesWithCollection(Collection $data): Collection
    {

        $this->data = $data;

        $data->each(function (BaccaratLotteryLog $record) {

            $result = $record->transformationResult;

            if ($result === 'T') {
                // 平局不改变X和Y的坐标
                $record->x = $this->x;
                $record->y = $this->y;
                return;
            }

            if ($result === $this->lastResult) {
                if ($this->y > $this->minY) {
                    $this->y--;
                } else {
                    $this->x++;
                }
                list($this->x, $this->y) = $this->checkAndSetCoordinate($this->x, $this->y);
            } else {
                if ($this->lastResult !== '') {
                    $this->x = $this->startXCoordinates[$this->lastResult] + 1;
                    $this->y = 6;
                }
                $this->startXCoordinates[$result] = $this->x;
                $this->minY = 1;
            }

            list($this->x, $this->y) = $this->checkAndSetCoordinate($this->x, $this->y);
            $this->lastResult = $result;
            $record->x = $this->x;
            $record->y = $this->y;
        });

        return $data;
    }

    protected function checkAndSetCoordinate($x, $y): array
    {
        foreach ($this->data as $record) {
            if ($record->x === $x && $record->y === $y && $record->transformationResult !== 'T') {
                // 防止Y坐标小于1
                $this->minY = $y + 1;
                return $this->checkAndSetCoordinate($x + 1, $y + 1);
            }
        }
        return [$x, $y];
    }
}
