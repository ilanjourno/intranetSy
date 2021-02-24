<?php

namespace App\Controller;

use App\Entity\CustomerFiles;
use App\Entity\Files;
use App\Form\FilesType;
use App\Repository\ClientStatutDocumentRepository;
use App\Repository\CustomerFilesRepository;
use App\Repository\CustomerFilesStatutRepository;
use App\Repository\FilesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\FileUploader;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @Route("/files/{id}", requirements={"id":"\d+"})
 */
class FilesController extends AbstractController
{

    private $customer;
    /**
     * @Route("/", name="files_index", methods={"GET", "POST"})
     */
    public function index(Request $request, CustomerFiles $customer, DataTableFactory $dataTableFactory): Response
    {
        $this->customer = $customer;

        $table = $dataTableFactory->create()
        ->add('id', NumberColumn::class, ['label' => '#'])
        ->add('preview', TextColumn::class, [
            'data' => function($context) {
                return $context->getFile();
            },
            'render' => function($value, $context){
                return sprintf("<embed src='/uploads/files/$value' width='200px'/>");
            },
            'label' => 'Aperçu'
        ])
        ->add('document', TextColumn::class, [
            'label' => 'Document', 
            'data' => function($context, $data){
                return $data ?? 'Image libre';
            }
        ])
        ->add('file', TextColumn::class, ['label' => 'Nom du fichier'])
        ->add('created_at', DateTimeColumn::class, ['label' => 'Créer le', 'format' => 'd-m-Y H:i:s'])
        ->add('updated_at', DateTimeColumn::class, ['label' => 'Modifier le', 'format' => 'd-m-Y H:i:s'])
        ->add('actions', TextColumn::class, [
            'data' => function($context) {
                return $context->getId();
            }, 
            'render' => function($value, $context){
                $show = sprintf('<a href="%s" class="btn btn-primary">Regarder</a>', $this->generateUrl('files_show', ['file' => $value, 'id' => $this->customer->getId()]));
                $edit = sprintf('<a href="%s" class="btn btn-primary">Modifier</a>', $this->generateUrl('files_edit', ['file' => $value, 'id' => $this->customer->getId()]));
                return $show.$edit;
            }, 
            'label' => 'Actions'
        ])
        ->createAdapter(ORMAdapter::class, [
            'entity' => Files::class,
            'query' => function (QueryBuilder $builder) {
                return $builder
                ->select('f')
                ->from(Files::class, 'f')
                ->leftJoin('f.customerFiles', 'customerFiles')
                ->where('customerFiles = :i')
                ->setParameter('i', $this->customer);
            }
        ])->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('files/index.html.twig', [
            'customer_id' => $customer->getId(),
            'datatable' => $table
        ]);
    }

    /**
     * @Route("/new", name="files_new", methods={"GET","POST"})
     */
    public function new(Request $request, CustomerFiles $customer, FileUploader $fileUploader, ClientStatutDocumentRepository $clientStatutDocumentRepository): Response
    {
        if ($request->isMethod('post')) {
            if($request->files->get('files') !== null){
                $files = $request->files->get('files')['file'];
                foreach ($files as $documentId => $value) {
                    $product = new Files(); 
                    $product->setFile($value);
                    $file = $product->getFile();
                    $fileName = $fileUploader->upload($file);
                    $product->setFile($fileName);
                    $product->setCustomerFiles($customer);
                    $document = $clientStatutDocumentRepository->find($documentId);
                    $product->setDocument($document);
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($product);
                    $entityManager->flush();
                }
                return $this->redirectToRoute('files_index', ['id' => $customer->getId()]);
            }
            
            $this->addFlash('error', 'Vous n\'avez rentrer aucun fichier !');
            return $this->redirectToRoute('files_new', ['id' => $customer->getId()]);
        }
        
        return $this->render('files/new.html.twig', [
            'statuts' => $clientStatutDocumentRepository->findStatut($customer),
            'client_statut' => $customer->getClientStatutId()->getName(),
            'customer_id' => $customer->getId(),
        ]);
    }

    /**
     * @Route("/{file}", name="files_show", methods={"GET"}, requirements={"file":"\d+"})
     */
    public function show(CustomerFiles $customer, Files $file): Response
    {
        return $this->render('files/show.html.twig', [
            'file' => $file,
            'customer_id' => $customer->getId(),
        ]);
    }

    /**
     * @Route("/{file}/edit", name="files_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, CustomerFiles $customer, Files $file, FileUploader $fileUploader): Response
    {
        $oldFile = $file->getFile();
        $form = $this->createForm(FilesType::class, $file, ['customer' => $customer]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fileUploader->delete($oldFile);
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('files_index', ['id' => $customer->getId()]);
        }

        return $this->render('files/edit.html.twig', [
            'file' => $file,
            'customer_id' => $customer->getId(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{file}", name="files_delete", methods={"DELETE"})
     */
    public function delete(Request $request, CustomerFiles $customer, Files $file, FileUploader $fileUploader): Response
    {
        if ($this->isCsrfTokenValid('delete'.$file->getId(), $request->request->get('_token'))) {
            $fileUploader->delete($file->getFile());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($file);
            $entityManager->flush();
        }

        return $this->redirectToRoute('files_index', ['id' => $customer->getId()]);
    }
}
