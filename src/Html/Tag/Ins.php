<?php

namespace tourze\Html\Tag;

use tourze\Html\Element\InlineElement;
use tourze\Html\Tag;

/**
 * <ins> 标签定义已经被插入文档中的文本
 *
 * @property mixed cite      指向另外一个文档的URL，此文档可解释文本被删除的原因。
 * @property mixed datetime  定义文本被删除的日期和时间
 */
class Ins extends Tag implements InlineElement
{

    protected $_tagName = 'ins';

    /**
     * @return null|string|array
     */
    public function getCite()
    {
        return $this->getAttribute('cite');
    }

    /**
     * @param $cite
     */
    public function setCite($cite)
    {
        $this->setAttribute('cite', $cite);
    }

    /**
     * @return null|string|array
     */
    public function getDatetime()
    {
        return $this->getAttribute('datetime');
    }

    /**
     * @param $datetime
     */
    public function setDatetime($datetime)
    {
        $this->setAttribute('datetime', $datetime);
    }

}
