<?php

namespace app\tlr\model;

use think\Model;    //  导入think\Model类


class EnrollModel extends Model
{
    protected $table = 'enroll';

    protected function getContentAttr($content)
    {
        $cont = str_replace("\n", "<br>", $content);
        $cont = str_replace(" ", "&nbsp;&nbsp;", $cont);
        return $cont;
    }
}
