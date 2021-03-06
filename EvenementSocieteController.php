<?php


namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Evenement;

use App\Form\EventType;
use App\Repository\EvenementRepository;
use Doctrine\Persistence\ObjectManager;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Constraints\DateTime;

class EvenementSocieteController extends AbstractController
{




    /**
     * @Route("/pdf{id}", name="pdf")
     */
    public function pdf(int $id): Response
    {

        $rep=$this->getDoctrine()->getRepository(Evenement::class);
        $evenement=$rep->find($id);

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($pdfOptions);

        $html = $this->renderView('evenement_societe/eventmail.html.twig', [
            'title' => "Welcome to our PDF Test",'evenement' => $evenement
        ]);

        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        $dompdf->stream("mmypdf.pdf", [
            "Attachment" => false
        ]);

        // Send some text response
        return new Response("sd");
    }

    /**
     * @Route("/eventinfo{id}", name="eventinfo")
     */
    public function show(int $id): Response
    {
        $rep=$this->getDoctrine()->getRepository(Evenement::class);
        $entityManager = $this->getDoctrine()->getManager();

        $evenement=$rep->find($id);
        dump($evenement);
        $evenement->setViewed($evenement->getViewed()+1);
        $entityManager->persist($evenement);
        $entityManager->flush();

        return $this->render('evenement_societe/eventinfo.html.twig', [
            'evenement' => $evenement,
        ]);


    }

    /**
     * @Route("/manager", name="manager")
     */
    public function manager(): Response
    {

        $rep=$this->getDoctrine()->getRepository(Evenement::class);
        $evenement=$rep->findAll();


        return $this->render('evenement_societe/evenementmanager.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    /**
     * @Route("/sortbytitleasc", name="sortbytitleasc")
     */
    public function sortByTitleASC(): Response
    {

        $rep=$this->getDoctrine()->getRepository(Evenement::class);
        $evenement=$rep->sortByTitleASC();


        return $this->render('evenement_societe/evenementmanager.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    /**
     * @Route("/sortbytitledesc", name="sortbytitledesc")
     */
    public function sortByTitleDESC(): Response
    {
        $rep=$this->getDoctrine()->getRepository(Evenement::class);
        $evenement=$rep->sortByTitleDESC();
        return $this->render('evenement_societe/evenementmanager.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    /**
     * @Route("/socdeleteevenement{id}", name="socdeleteevenement")
     */
    public function deleteevent(int $id): Response
    {

        $entityManager = $this->getDoctrine()->getManager();
        $event = $entityManager->getRepository(Evenement::class)->find($id);
        $entityManager->remove($event);
        $title = $event->getTitle();



        $entityManager->flush();
        $entityManager->flush();

        return $this->redirectToRoute("manager");
    }



    /**
     * @Route("/addevent", name="addevent")
     */
    public function AddEvent(Request $request)
    {
        $event= new Evenement();
        $form=$this->createForm(EventType::class,$event);
        $form->add('Add',SubmitType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $file = $request->files->get('event')['my_picture'];
            $upload_directory = $this->getParameter('upload_directory');
            $filename = md5(uniqid()).'.'.$file->guessExtension();
            $file->move(
                $upload_directory,
                $filename
            );

            $entityManager = $this->getDoctrine()->getManager();

            $event->setPicture($filename);

            $entityManager->persist($event);
            $entityManager->flush();
            return $this->redirectToRoute("manager");
        }

        return $this->render('evenement_societe/addevent.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/updateevent{id}", name="updateevent")
     */
    public function UpdateEvent(Request $request,$id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $event = $entityManager->getRepository(Evenement::class)->find($id);

        $form=$this->createForm(EventType::class,$event);
        $form->add('Update',SubmitType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $title = $event->getTitle();


            //$entityManager->flush();

            $entityManager->flush();
            return $this->redirectToRoute("manager");
        }

        return $this->render('evenement_societe/updateevent.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    /**
     * @Route("/searchevenement ", name="searchevenement")
     */
    public function searchevenement(Request $request)
    {
        $repository = $this->getDoctrine()->getRepository(Evenement::class);
        $requestString=$request->get('searchValue');

        $evenement = $repository->findEvenementByTitle($requestString);


        $response = new Response();

        $encoders = array(new XmlEncoder(), new JsonEncode());
        $normalizers = array(new GetSetMethodNormalizer());

        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($evenement, 'json');
        dump($jsonContent);

        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($jsonContent);


        return $response;
    }
    /**
     * @Route("/listu1", name="listu1", methods={"GET"})
     */
    public function listu1(EvenementRepository $evenementRepository): Response
    {
        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);
        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('evenement_societe\pdf.html.twig', [
            'event' => $evenementRepository->findAll(),
        ]);

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A2', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();
        // Output the generated PDF to Browser (inline view)
        $dompdf->stream("mypdf.pdf", [
            "Attachment" => false
        ]);
    }

}