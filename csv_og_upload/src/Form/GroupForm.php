<?php
/**
 * @file
 * Contains \Drupal\csv_og_upload\Form\OgForm.
 */
namespace Drupal\csv_og_upload\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;




class GroupForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_table_form';
  }

   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['csv_upload'] = array(
			'#type' => 'managed_file',
			'#title' => $this->t('Upload CSV For groups 1'),
			'#upload_location' => 'public://og',
			'#upload_validators' => [
				'file_validate_extensions' => ['csv'],
			],
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#button_type' => 'primary',
    );
    return $form;
  }


 /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
		
    \Drupal::database()->truncate('groups')->execute();
    \Drupal::database()->truncate('groups_field_data')->execute();
    \Drupal::database()->truncate('group_content_field_data')->execute();
    \Drupal::database()->truncate('group_content')->execute();
    /* Fetch the array of the file stored temporarily in database */
    $csv_file = $form_state->getValue('csv_upload');
    /* Load the object of the file by it's fid */
    $file = File::load( $csv_file[0] );

    /* Set the status flag permanent of the file object */
    $file->setPermanent();

    /* Save the file in database */
    $file->save();

    // You can use any sort of function to process your data. The goal is to get each 'row' of data into an array
    // If you need to work on how data is extracted, process it here.
		$data = $this->csvtoarray($file->getFileUri(), ',');
		$connection = \Drupal::database();
	//	print("<pre>");
    //	print_r($data);exit();
    $uuid_service = \Drupal::service('uuid');
    $sl_no = 1;
    foreach($data as $row) {
        $date = date_create($row['created']);
        $date_changed = date_create($row['changed']);
//			print_r($row['Entity id']);exit();
			$result = $connection->insert('groups')
			->fields([
          'type' => str_replace(' ', '_', strtolower(substr($row['label'],0,22))),
          // 'id' =>  $sl_no,
          'uuid' => $uuid_service->generate(),
          'langcode' => 'en',
          ])
          ->execute();

          // Group field data
          $result1 = $connection->insert('groups_field_data')
          ->fields([
          'label' =>  $row['label'],
          'type' =>  str_replace(' ', '_', strtolower(substr($row['label'],0,22))),
          'id' =>  $sl_no,
          'uid' => $row['uid'],
          'created' => date_timestamp_get($date),
          'changed' => date_timestamp_get($date_changed),
          'default_langcode' => 1,
          'langcode' => 'en',
          ])
          ->execute();
          $sl_no = $sl_no +1;
    }

    // $batch = array(
    //   'title' => t('Importing Data...'),
    //   'operations' => $operations,
    //   'init_message' => t('Import is starting.'),
    //   'finished' => '\Drupal\IMPORT_EXAMPLE\addImportContent::addImportContentItemCallback',
    // );
    // batch_set($batch);
		
	}



	public function csvtoarray($filename='', $delimiter){

    if(!file_exists($filename) || !is_readable($filename)) return FALSE;
    $header = NULL;
    $data = array();

    if (($handle = fopen($filename, 'r')) !== FALSE ) {
      while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
      {
        if(!$header){
          $header = $row;
        }else{
          $data[] = array_combine($header, $row);
        }
      }
      fclose($handle);
    }

    return $data;
  }




}