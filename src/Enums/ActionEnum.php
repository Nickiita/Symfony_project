<?php 
 
namespace App\Enums; 
 
use Doctrine\ORM\Query\Expr\Func; 
 
enum ActionEnum: string 
{ 
    case BUY = 'buy'; 
    case SELL = 'sell'; 
 
    public function getOpposite(): ActionEnum 
    { 
        return match($this){ 
            self::BUY => self::SELL, 
            self::SELL => self::BUY, 
        }; 
    } 
}