<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Comment extends Entity
{
    protected array $_accessible = [
        'body' => true,
        'user_id' => true,
    ];
}
