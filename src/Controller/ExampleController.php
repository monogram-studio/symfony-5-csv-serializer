<?php declare(strict_types=1);

namespace App\Controller;

use App\Model\Person;
use Nette\Utils\FileSystem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ExampleController extends AbstractController
{
    #[Route(path: '/', name: 'app.example.index')]
    public function index(): Response
    {
        $csvExtension = 'csv';
        $projectDirectory = $this->getParameter(name: 'kernel.project_dir');
        $dateTime = new \DateTimeImmutable();
        $serializer = new Serializer(
            normalizers: [new ObjectNormalizer(nameConverter: new CamelCaseToSnakeCaseNameConverter())],
            encoders: [new CsvEncoder()]
        );
        $personsFileContent = FileSystem::read($projectDirectory.'/files/persons.'.$csvExtension);
        // From CSV formatted string to PHP array
        $personsDataDecoded = $serializer->decode(
            data: $personsFileContent,
            format: $csvExtension,
            context: [CsvEncoder::DELIMITER_KEY => ',']
        );
        // Add new Person
        $newPerson = (new Person())
            ->setFirstName('Lorem')
            ->setLastName('Ipsum')
            ->setEmail('lorem.ipsum@example.com')
        ;
        $personsDataDecoded[] = $newPerson->toArray();
        // From PHP array to CSV formatted string
        $personsDataEncoded = $serializer->encode(data: $personsDataDecoded, format: $csvExtension);
        // Forces download the response as CSV file
        $response = new StreamedResponse(function () use ($personsDataEncoded) {
            echo $personsDataEncoded;
        });
        $response->headers->set(key: 'Content-Type', values: $csvExtension);
        $disposition = HeaderUtils::makeDisposition(
            disposition: HeaderUtils::DISPOSITION_ATTACHMENT,
            filename: 'new-persons-'.$dateTime->getTimestamp().'.'.$csvExtension
        );
        $response->headers->set('Content-Disposition', $disposition);
        return $response;
    }
}