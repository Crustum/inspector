<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Team extends Entity
{
    protected array $_accessible = [
        'name' => true,
        'owner_id' => true,
    ];
}
