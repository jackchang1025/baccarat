<?php

namespace App\Baccarat\Service\Platform\Bacc;

use Hyperf\Contract\ConfigInterface;

class AccountManager
{
    private array $accounts = [];
    private int $currentIndex = 0;

    public function __construct(ConfigInterface $config)
    {
        $accountConfigs = $config->get('baccarat.platform.bacc.accounts', []);
        foreach ($accountConfigs as $name => $config) {

            if(!empty($config['cookies'])){
                $this->accounts[] = new Account($name, $config);

            }
        }
    }

    public function getNextAccount(): ?Account
    {
        $total = count($this->accounts);
        if ($total === 0) {
            return null;
        }

        $attempts = 0;
        do {
            $account = $this->accounts[$this->currentIndex];
            $this->currentIndex = ($this->currentIndex + 1) % $total;
            
            if ($account->isHealthy()) {
                return $account;
            }
            
            $attempts++;
        } while ($attempts < $total);

        return null; // 所有账号都不健康时返回null
    }

    public function markFailure(Account $account): void
    {
        $account->markFailure();
    }
} 