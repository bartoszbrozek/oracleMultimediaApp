<?php

namespace AppBundle\Controller;

use AppBundle\Form\ImageType;
use Doctrine\ORM\Configuration;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\DBAL\Driver\OCI8;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $form = $this->uploadImage($request);
        return $this->render('default/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function getOciConnection()
    {
        $conn = oci_connect('bartek', 'pass1234', 'localhost/db1');
        if ($conn) {
            return $conn;
        }

        $e = oci_error();
        trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
    }

    private function getAllImages()
    {
        $conn = $this->getOciConnection();
        $stid = oci_parse($conn, 'SELECT image FROM image_table WHERE id = 1');
        oci_execute($stid);

        return oci_fetch_assoc($stid);
    }

    private function getImageProperties($id)
    {
        $sql = "
                DECLARE
                 image ORDImage;
                 idnum integer;
                BEGIN
                     SELECT id, image into idnum, image from image_table where id=:id;
                     RETURN image;
                END;
                ";
        $conn = $this->getOciConnection();
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ":id", $id);
        oci_execute($stid);

        return oci_fetch_assoc($stid);
    }


    private function createEmptyLob()
    {
        $lastId = $this->getLastId() + 1;
        $sql = '
                INSERT INTO image_table (id, image) VALUES (:id, ORDImage.init())
                ';
        $conn = $this->getOciConnection();
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ":id", $lastId);
        oci_execute($stid);

        return $lastId;
    }


    private function getLastId()
    {
        $sql = '
                SELECT id FROM image_table ORDER BY id DESC OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY
                ';
        $conn = $this->getOciConnection();
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);

        $result = oci_fetch_row($stid);
        return $result[0];
    }

    private function insertImage($filename)
    {
        $lastId = $this->createEmptyLob();

        $sql = "DECLARE
                  obj ORDIMAGE;
                  ctx RAW(64) := NULL;
                BEGIN
                  select Image into obj from image_table where id = :lastId for update;
                  obj.setSource('file','MEDIADIR',:filename);
                  obj.import(ctx);
                  update image_table set image = obj where id = :lastId;
                commit;
                END;";

        $conn = $this->getOciConnection();
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ":lastId", $lastId);
        oci_bind_by_name($stid, ":filename", $filename);
        oci_execute($stid);
    }

    private function uploadImage(Request $request)
    {
        $form = $this->createForm(ImageType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $dir = "C:\\mediadir";
            $file = $form['attachment']->getData();

            $file->move($dir, $file->getClientOriginalName());

            // Now insert image into database
            $this->insertImage($file->getClientOriginalName());
            $this->addFlash('notice', "Successfully added a new image!");
        }

        return $form;
    }
}
