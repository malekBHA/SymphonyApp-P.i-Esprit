<?php

namespace App\Controller;
use Dompdf\Dompdf;
use App\Entity\Reponse;

use App\Form\ReponseType;
use App\Entity\Reclamation;
use Symfony\Component\Mime\Email;
use App\Repository\ReponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReclamationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class ReponseController extends AbstractController
{
    #[Route('/reponse', name: 'app_reponse')]
    public function index(Request $request, ReclamationRepository $reclamationRepository): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            throw $this->createAccessDeniedException('User not authenticated');
        }

        // Assuming 'getId' gets the ID of the current user, which corresponds to the 'medecin' attribute in Reclamation
        $reclamations = $reclamationRepository->findByMedecin($currentUser->getId());

        return $this->render('reponse/index.html.twig', [
            'controller_name' => 'ReponseController',
            'reclamations' => $reclamations
        ]);
    }


   #[Route('/reponse/new/{reclamationId}', name: 'newRep', methods: ['GET', 'POST'])]
    public function new(Request $request, $reclamationId, MailerInterface $mailer): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        // Fetch the Reclamation entity by ID
        $reclamation = $entityManager->getRepository(Reclamation::class)->find($reclamationId);

        if (!$reclamation) {
            throw $this->createNotFoundException('Reclamation not found for id: ' . $reclamationId);
        }

        $reponse = new Reponse();
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set the Reclamation for the Reponse entity
            $reponse->setReclamation($reclamation);

            // Extract the ID from the User object and set it as the patient
            $patientId = $reclamation->getUser()->getId();
            $reponse->setPatient($patientId);

            $etat = $form->get('etat')->getData();
            $reclamation->setEtat($etat);

            $entityManager->persist($reponse);
            $entityManager->persist($reclamation);
            $entityManager->flush();

            // Set email subject based on Reclamation's Sujet
            $SujetReclamation = $reclamation->getSujet();
            $email = (new Email())
                ->from('testp3253@gmail.com')
                ->to('malek.belhadjamor@gmail.com')
                ->subject('Reponse sur votre reclamation : Sujet: ' . $SujetReclamation)
                ->text($reponse->getMessage());
            $mailer->send($email);

            // Flash message and redirect
            $this->addFlash('success', 'Reponse created successfully.');
            return $this->redirectToRoute('reponse_show_medecin', ['reclamationId' => $reclamationId]);
        }

        // Render the form template with necessary variables
        return $this->render('reponse/formRep.html.twig', [
            'form' => $form->createView(),
            'reclamationId' => $reclamationId,
            'sujet' => $reclamation->getSujet()
        ]);
    }
    


    #[Route("/reponses", name:"reponse_show")]
public function show(Request $request, ReclamationRepository $reclamationRepository): Response
{
    $reclamationId = $request->query->get('reclamationId');
    $reclamation = null;
    $reponses = [];
    $errorMessage = '';
    $currentUser = $this->getUser();

        $reclamations = $reclamationRepository->findReclamationsByUserId($currentUser->getId());
        
        if ($reclamation) {
            foreach ($reclamations as $reclamation)
            $reponses = $reclamation->getReponses();
        } else {
            $errorMessage = 'Reclamation not found for id: ' . $reclamationId;
        }
    
    return $this->render('reponse/ShowReponse.html.twig', [
        'reclamations' => $reclamations,
        'reponses' => $reponses,
        'errorMessage' => $errorMessage
    ]);
}

#[Route("/reponsesMedecin", name:"reponse_show_medecin")]
public function showMedecin(Request $request, ReclamationRepository $reclamationRepository): Response
{
    $reclamationId = $request->query->get('reclamationId');
    $reclamation = null;
    $reponses = [];
    $errorMessage = '';
    $currentUser = $this->getUser();

        $reclamations = $reclamationRepository->findByMedecin($currentUser->getId());
        
        if ($reclamation) {
            foreach ($reclamations as $reclamation)
            $reponses = $reclamation->getReponses();
        } else {
            $errorMessage = 'Reclamation not found for id: ' . $reclamationId;
        }
    
    return $this->render('reponse/ShowReponseMedecin.html.twig', [
        'reclamations' => $reclamations,
        'reponses' => $reponses,
        'errorMessage' => $errorMessage
    ]);
}



    


        #[Route('/delete/response/{id}', name: 'app_response_delete', methods: ['GET'])]
        
        public function deleteResponse(Request $request, int $id, EntityManagerInterface $entityManager): Response
{
    $response = $entityManager->getRepository(Reponse::class)->find($id);

    if (!$response) {
        throw $this->createNotFoundException('Response not found');
    }

    // Find the reclamation associated with the response
    $reclamation = $response->getReclamation();

    if (!$reclamation) {
        throw $this->createNotFoundException('Reclamation not found');
    }

    // Remove the response from the reclamation
    $reclamation->removeReponse($response);

    // Remove the response itself
    $entityManager->remove($response);
    $entityManager->flush();

    return new Response('Response deleted successfully', Response::HTTP_OK);
}

        
}
    

    
 

