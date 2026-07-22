<?php
declare(strict_types=1);

namespace App\Domain\Orders\Model\Entity;

use Cake\ORM\Entity;

class Payment extends Entity
{
    protected array $_accessible = [
        'amount' => true,
    ];
}
