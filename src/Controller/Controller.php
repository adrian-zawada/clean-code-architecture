<?php
declare(strict_types=1);

namespace App\Controller;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class Controller extends AbstractController
{

    function index()
    {
        return new JsonResponse('ReallyDirty API v1.0');
    }

    function doctor(Request $request)
    {
        if ($request->getMethod() === 'GET') {

            $doctorId = $request->get('id');
            $doctor = $this->getDoctorById($doctorId);

            if ($doctor) {
                return new JsonResponse([
                    'id' => $doctor->getId(),
                    'firstName' => $doctor->getFirstName(),
                    'lastName' => $doctor->getLastName(),
                    'specialization' => $doctor->getSpecialization(),
                ]);
            } else {
                return new JsonResponse([], 404);
            }
        } elseif ($request->getMethod() === 'POST') {
            $doctor = $this->createDoctorFromRequest($request);
            $this->saveDoctor($doctor);

            return new JsonResponse(['id' => $doctor->getId()]);
        }

        //TODO other methods?
    }

    function slots(int $doctorId, Request $request)
    {
        $doctor = $this->getDoctorById((int)$doctorId);

        if ($doctor) {

            if ($request->getMethod() === 'GET') {
//get slots
                /** @var SlotEntity[] $array */
                $slots = $doctor->slots();

                if (count($slots)) {
                    $res = [];
                    foreach ($array as $slot) {
                        $res[] = [
                            'id' => $slot->getId(),
                            'day' => $slot->getDay()->format('Y-m-d'),
                            'from_hour' => $slot->getFromHour(),
                            'duration' => $slot->getDuration()
                        ];
                    }
                    return new JsonResponse($res);
                } else {
                    return new JsonResponse([]);
                }
            } elseif ($request->getMethod() === 'POST') {
                $newSlot = $this->createSlotFromRequest($request, $doctor);
                $slot = $this->saveSlot($newSlot);

                return new JsonResponse(['id' => $slot->getId()]);
            }
        } else {
            return new JsonResponse([], 404);
        }
    }

    private function getDoctorById(int $doctorId): ?DoctorEntity {
        $entityManager = $this->getDoctrine()->getManager();

        return $entityManager->createQueryBuilder()
            ->select('doctor')
            ->from(DoctorEntity::class, 'doctor')
            ->where('doctor.id=:id')
            ->setParameter('id', $doctorId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function createSlotFromRequest(Request $request, DoctorEntity $doctor) : SlotEntity {
        $slot = new SlotEntity();
        $slot->setDay(new DateTime($request->get('day')));
        $slot->setDoctor($doctor);
        $slot->setDuration((int)$request->get('duration'));
        $slot->setFromHour($request->get('from_hour'));

        return $slot;
    }

    private function saveSlot(SlotEntity $slot): SlotEntity {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($slot);
        $entityManager->flush();

        return $slot;
    }

    private function createDoctorFromRequest(Request $request): DoctorEntity {
        $doctor = new DoctorEntity();
        $doctor->setFirstName($request->get('firstName'));
        $doctor->setLastName($request->get('lastName'));
        $doctor->setSpecialization($request->get('specialization'));

        return $doctor;
    }

    private function saveDoctor(DoctorEntity $doctor): DoctorEntity {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($doctor);
        $entityManager->flush();

        return $doctor;
    }

}
