<?php

namespace Drupal\csv_og_upload\Controller;

use Drupal\Core\Controller\ControllerBase;

class DateFieldTransfer extends ControllerBase{

    public function update(){
        $nids = \Drupal::entityQuery('node')
            ->condition('type','article')
            ->execute();
        \Drupal::database()->truncate('node__field_date_date')->execute();
        foreach($nids as $nid){
            $this->date_field_transfer($nid);
        }
        // \Drupal::database()->truncate('node__field_date')->execute();
        $build = [
                '#markup' => $this->t('Date fields Transferred!'),
            ];
            return $build;
    }

    function date_field_transfer($nid){
        $entity =  \Drupal\node\Entity\Node::load($nid);
        $date_timestamp = $entity->get('field_date')->getValue()[0]['value'];
        if(!empty($date_timestamp)){
            $entity->set('field_date_date', date('Y-m-d', $date_timestamp));
            // $entity->set('field_date', '');
            $entity->save();
        }
    }

}