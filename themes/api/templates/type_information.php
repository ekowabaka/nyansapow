<?= str_replace(' ', '&nbsp;', ($abstract ? 'abstract ' : '') . ($final ? 'final ' : '') . ("{$visibility} ") . ($static ? 'static ' : '')) . t('type_link', $type) ?>