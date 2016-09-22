<?php
$page_num_list = $this->getPageNumList();//获取要显示的页码
$current_page=$this->getCurrent();//获取当前页码
$total_page = $this->getPageNum();//获取总页码
if(count($page_num_list)>0){
    $html = '';
    if ($current_page > 1) {
        $html.='<a href="' . $this->getUrl($this->getPrev()) . '" target="_self" class="hko kos" title="上一页"><<</a>';
    }
    foreach($page_num_list as $i){
        if ($current_page == $i) {
            $html.='<a href="' . $this->getUrl($i) . '" class="curt" target="_self" >' . $i . '</a>';    //输出页数
        } else {
            $html.='<a href="' . $this->getUrl($i) . '"  target="_self" >' . $i . '</a>';    //输出页数
        }
    }
    $html .= '<span>...</span>';
    $html .= '<a href="'.$this->getUrl($total_page).'" target="_self">' . $total_page . '</a>';
    if ($current_page < $total_page) {
        $html .= '<a href="' . $this->getUrl($this->getNext()) . '" target="_self" class="hko" title="下一页">>></a>';
    }
    echo $html;
}