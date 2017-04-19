<?php

namespace AppBundle\Controller;

use Doctrine\ORM\Configuration;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;

use Doctrine\DBAL\Driver\OCI8;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
//
//        $sql = "
//        SELECT name,
//               event_type,
//               sport_type,
//               level
//          FROM vnn_sport
//    ";
//
//        $em = $this->getDoctrine()->getManager();
//        $stmt = $em->getConnection()->prepare($sql);
//        $stmt->execute();
//        return $stmt->fetchAll();


        $tns = "  
  (DESCRIPTION =
    (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = 1521))
    (CONNECT_DATA =
      (SERVER = DEDICATED)
      (SERVICE_NAME = db1)
    )
  )
       ";
        $db_username = "bartek";
        $db_password = "pass1234";
        try {
            $dbh = new \PDO("oci:dbname=" . $tns, $db_username, $db_password);
        } catch (PDOException $e) {
            echo($e->getMessage());
        }


        $s = $dbh->prepare("select * from table1");
        $s->execute();

        $data = $s->fetchAll();

        var_dump($data);

        while (($r = $s->fetch(\PDO::FETCH_ASSOC)) != false) {
            print_r($r);
        }


        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
        ]);
    }
}
