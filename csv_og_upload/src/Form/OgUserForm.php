<?php
/**
 * @file
 * Contains \Drupal\csv_og_upload\Form\OgForm.
 */
namespace Drupal\csv_og_upload\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;




class OgUserForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'og_user_upload_form';
  }

   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['csv_upload'] = array(
			'#type' => 'managed_file',
			'#title' => $this->t('Upload CSV'),
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
	
    /* Fetch the array of the file stored temporarily in database */
    \Drupal::database()->truncate('og_membership')->execute();
    exit();
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
		
    $uuid_service = \Drupal::service('uuid');
    $i =0;
    foreach($data as $row) {
        $date = date_create($row['Created']);
        $result = $connection->insert('og_membership')
        ->fields([
            'type' =>  'default',
            // 'id' =>  $i,
            'uuid' => $uuid_service->generate(),
            'entity_id' => $row['Group ID'],
            'entity_bundle' => $row['Entity Type'],
            'entity_type' =>'node',
            'uid' =>  $row['Uid'],
            'language' => 'und',
            'created' =>  date_timestamp_get($date),
            'state' =>  'active',


        ])
        ->execute();
        $i = $i +1;
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