<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 25.12.19
 * Time: 22:04
 */

namespace App\BasketOrderBundle\Model;


class BaseEntity
{
    public function iterateVisible() {
        $out = [];
        $object = $this ;
        foreach ($object as $key => $value) {
            $value ? $out[$key] = $value : null;
            //print "$key => $value\n";
        }

        return $out;
    }
}