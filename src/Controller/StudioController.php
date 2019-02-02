<?php

namespace App\Controller;

use App\Form\SearchFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Config\FileLocator;

class StudioController extends AbstractController
{

    /**
     * @Route("/", name="studio_index")
     * @Method({"GET"})
     */
    public function index(Request $request)
    {
        $form = $this->createForm(SearchFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $searchFormData = $form->getData();

            $studios = $this->searchStudios($searchFormData);

            $topRatedStudio = [];
            $topRatedAffordableStudios = [];
            if (!empty($studios)) {

                $topRatedStudio = $this->getTopRatedStudios($studios);
                $topRatedAffordableStudios = $this->getTopRatedAffordableStudio($topRatedStudio);

                if (!empty($topRatedAffordableStudios)) {
                    foreach ($topRatedAffordableStudios as $fkey => $topStudios) {
                        foreach ($studios as $skey => $studio) {

                            if ($studio['id'] == $topStudios['id']) {
                                unset($studios[$skey]);
                            }

                        }
                    }
                }

            }

            return $this->render('search/search.html.twig', [
                'studios' => $studios,
                'top_rated' => $topRatedStudio,
                'top_affordable_rated' => $topRatedAffordableStudios,
                'our_form' => $form->createView()
            ]);

        }

        return $this->render('studios/index.html.twig', [
            'our_form' => $form->createView()
        ]);

    }


    /**
     * @param array $searchData
     * @return array|mixed
     */
    private function searchStudios($searchData = [])
    {

        if (!empty($searchData) && is_array($searchData)) {

            // start getting the studios data from the json file
            $configDirectories = [__DIR__];
            $fileLocator = new FileLocator($configDirectories);
            $studiosRessourceFile = $fileLocator->locate('../../ressources/Studios.json', false, false);
            $content = file_get_contents($studiosRessourceFile[0]);
            $studiosJson = json_decode($content, true);
            // end getting the studios data from the json file
            
            //basic filtering of the studios based on the form's preferences
            $filteredStudios = [];
            foreach ($studiosJson as $skey => $studio) {


                if (strtolower($studio['city']) == strtolower($searchData['city']) && $studio['price'] >= (int)$searchData['price']) {

                    if ($searchData['24HoursOpen']) {
                        if ($studio['open'] && ($searchData['trainer'] == $studio['trainer'])) {
                            array_push($filteredStudios, $studio);
                        }
                    } else {

                        if (!$studio['open'] && $searchData['trainer'] == $studio['trainer']) {
                            array_push($filteredStudios, $studio);
                        }
                    }
                }
            }

            //TODO - filter with the PLZ


            //sort the studios ascending comparing the price
            usort($filteredStudios, function ($a, $b) {
                return $a['price'] > $b['price'] ? 1 : -1;
            });

            //calculate for every studio it's rating
            $filteredStudios = $this->calculateRating($filteredStudios);


            //return the searched studios
            return $filteredStudios;

        } else {
            return [];
        }

    }

    /**
     * @param array $studios
     * @return array
     */
    private function calculateRating($studios = [])
    {

        $ratedStudios = [];
        foreach ($studios as $skey => $studio) {

            $note = 0;

            switch ($studio['trainer']) {
                case 1:
                    $note += 3;
                    break;
                case 3:
                    $note += 1;
                    break;
                case 4:
                    $note += 1;
                    break;
            }


            switch (strtolower($studio['duschen'])) {
                case "kostenpflichtig":
                    $note += 1;
                    break;
                case "kostenlos":
                    $note += 3;
                    break;
            }

            switch (strtolower($studio['contract'])) {
                case 24:
                    $note += 0;
                    break;
                case 12:
                    $note += 1;
                    break;
                case 1:
                    $note += 4;
            }

            $studio["note"] = $note;
            array_push($ratedStudios, $studio);

        }


        return $ratedStudios;
    }

    /**
     * @param array $studios
     * @return array
     */
    private function getTopRatedStudios($studios = [])
    {

        $topRatedStudios = [];

        if (!empty($studios)) {

            //sort the studios ascending comparing the note
            usort($studios, function ($a, $b) {
                return $a['note'] < $b['note'] ? 1 : -1;
            });

            $bestRate = $studios[0]['note'];

            foreach ($studios as $skey => $studio) {
                if ($studio['note'] == $bestRate) {
                    array_push($topRatedStudios, $studio);
                }
            }

        }

        return $topRatedStudios;

    }

    private function getTopRatedAffordableStudio($studios = [])
    {

        $topRatedAffordableStudios = [];

        if (!empty($studios)) {

            usort($studios, function ($a, $b) {
                return $a['price'] < $b['price'] ? 1 : -1;
            });

            $heighestPrice = ($studios[0]['price'] * 50) / 100;

            foreach ($studios as $skey => $studio) {
                if ($studio['price'] <= $heighestPrice) {
                    array_push($topRatedAffordableStudios, $studio);
                }
            }
        }

        return $topRatedAffordableStudios;

    }


}