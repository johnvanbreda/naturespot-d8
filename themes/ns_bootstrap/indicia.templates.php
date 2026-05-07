<?php
global $indicia_templates;
$indicia_templates['controlWrap'] = "<div id=\"ctrl-wrap-{id}\" class=\"ctrl-wrap\">{control}</div>\n";
$indicia_templates['suffix'] = " \n";
$indicia_templates['requiredsuffix'] = '<span class="deh-required">*</span>'."\n";
$indicia_templates['two-col-50'] = '<div class="row">' .
    '<div class="col-md-6">{col-1}</div>' .
    '<div class="col-md-6">{col-2}</div>' .
'</div>';