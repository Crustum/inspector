<?php
declare(strict_types=1);

namespace App\Domain\Orders\Model\Entity;

use Cake\ORM\Entity;

class Shipment extends Entity
{
    protected array $_accessible = [
        'tracking' => true,
    ];
}
