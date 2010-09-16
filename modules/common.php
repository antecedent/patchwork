<?php

namespace Patchwork;

function upper_bound(array $array, $value)
{
    $count = count($array);
    $first = 0;
    while ($count > 0) {
        $i = $first;
        $step = $count >> 1;
        $i += $step;
        if ($value >= $array[$i]) {
               $first = ++$i; 
               $count -= $step + 1;
          } else {
              $count = $step;
          }
    }
    return $first;    
}    
