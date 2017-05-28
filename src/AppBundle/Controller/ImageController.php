<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ImageController extends Controller
{

    /**
     * @Route("/images/", name="showImageList")
     */
    public function showImageListAction(Request $request)
    {
        return $this->render('default/imageList.html.twig', [
            'images' => $this->getImageList(),
        ]);
    }

    /**
     * @Route("/image/{id}", name="showSingleImage")
     */
    public function showImageAction(Request $request, $id)
    {
        $image = $this->getImage($id);

        return $this->render('default/image.html.twig', [
            'image' => base64_encode($image),
        ]);
    }

    private function getImage($id)
    {
        $sql = '
                SELECT t.image.getcontent() AS image FROM image_table t WHERE t.id = :id
                ';
        $conn = $this->getOciConnection();
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ":id", $id);
        oci_execute($stid);

        $result = oci_fetch_assoc($stid);
        if ($result) {
            return $result['IMAGE']->load();
        }
    }

    private function getImageList()
    {
        $sql = '
                SELECT id FROM image_table
                ';
        $conn = $this->getOciConnection();
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);

        oci_fetch_all($stid, $images);
        return $images;
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

}
