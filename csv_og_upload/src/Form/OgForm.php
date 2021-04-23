<?php
/**
 * @file
 * Contains \Drupal\csv_og_upload\Form\OgForm.
 */
namespace Drupal\csv_og_upload\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;




class OgForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'og_upload_form';
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
		//$form_file = $form_state->getValue('csv_upload');
		
		// if (isset($form_file[0]) && !empty($form_file[0])) {
		// 	$file = File::load($form_file[0]);
		// 	$uri = $file->uri->value;
    //  	$file_contents_raw = file_get_contents($uri);
		// 	var_dump($file_contents_raw);exit();
		// 	$file->setPermanent();
		// 	$file->save();
		// }
    // drupal_set_message($this->t('@can_name ,Your application is being submitted!', array('@can_name' => $form_state->getValue('candidate_name'))));
    //  foreach ($form_state->getValues() as $key => $value) {
    //    drupal_set_message($key . ': ' . $value);
		//  }
    \Drupal::database()->truncate('node__og_audience')->execute();
    exit();
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
    foreach($data as $row) {
//			print_r($row['Entity id']);exit();
			$result = $connection->insert('node__og_audience')
			->fields([
				'bundle' =>  $row['Type'],
				'deleted' =>  $row['deleted'],
				'entity_id' => $row['Entity id'],
				'revision_id' =>  $row['Vid'],
				'langcode' => 'und',
				'delta' =>  $row['delta'],
				'og_audience_target_id' =>  $row['Group ID'],


			])
			->execute();
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