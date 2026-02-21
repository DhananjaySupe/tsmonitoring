<?php namespace App\Libraries;

require APPPATH.'/ThirdParty/vendor/autoload.php';
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class AwsS3
{
    protected $S3_KEY = '';
    protected $S3_SECRET = '';
    protected $S3_BUCKET = '';
    protected $S3_REGION = '';
    private $S3;
    public $result = null;
    public $error = '';

    function __construct() {
        $AppConfig = new \Config\AppConfig();
        $this->S3_KEY = $AppConfig->S3['key'];
        $this->S3_SECRET = $AppConfig->S3['secret'];
        $this->S3_BUCKET = $AppConfig->S3['bucket'];
        $this->S3_REGION = $AppConfig->S3['region'];
        $this->S3 = S3Client::factory(array('credentials'=>array('key'=>$this->S3_KEY,'secret'=>$this->S3_SECRET),'region'=>$this->S3_REGION,'version'=>'latest'));
    }

    public function setAccount(){
        $key = $this->S3_KEY;
        $secret = $this->S3_SECRET;
        $region = $this->S3_REGION;
        $this->S3 = S3Client::factory(array('credentials'=>array('key'=>$key,'secret'=>$secret),'region'=>$region,'version'=>'latest'));
    }

    public function url($filename){
         $bucket = $this->S3_BUCKET;
         $filename = $this->encodeFilename($filename);
         return "https://{$bucket}.s3.amazonaws.com/{$filename}";
    }

    public function upload($params=array()){
        try {
            if(isset($params['base64'])){
                $this->result = $this->S3->putObject(array('Bucket'=>$this->S3_BUCKET,'Key'=>$params['key'],'Body'=>$params['base64'],'ContentEncoding'=>'base64','ContentType'=>$params['type'],'StorageClass'=>'STANDARD'));
            } else {
                $opts = array('Bucket'=>$this->S3_BUCKET,'Key'=>$params['key'],'SourceFile'=>$params['file'],'StorageClass'=>'STANDARD');
                if (!empty($params['type'])) {
                    $opts['ContentType'] = $params['type'];
                }
                $this->result = $this->S3->putObject($opts);
            }
            return true;
        } catch (S3Exception $e) {
            $this->error = $e->getMessage();
            return false;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
   }

   public function delete($filename){
        $bucket = $this->S3_BUCKET;
        try {
            $this->result = $this->S3->deleteObject(array('Bucket'=>$bucket,'Key'=>$filename));
            return true;
        } catch (S3Exception $e) {
            $this->error = $e->getMessage();
            return false;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
   }

   private function encodeFilename($filename){
        $pos = strrpos($filename, '/') + 1;
        return substr($filename, 0, $pos) . rawurlencode(substr($filename, $pos));
   }

}
