<?php

declare(strict_types=1);

trait Semaphore {
    
    private function Lock(string $Id) {
        for ($i = 0; $i < 100; $i++) {
            if (IPS_SemaphoreEnter((string) $this->InstanceID . $Id, 1)) {
                return true;
            } else {
                IPS_Sleep(mt_rand(1, 5));
            }
        }

        return false;
    }

    private function unlock($Id) {
        IPS_SemaphoreLeave((string) $this->InstanceID . (string) $ident);
    }
}