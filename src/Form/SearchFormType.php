<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Config\FileLocator;

class SearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $configDirectories = [__DIR__];
        $fileLocator = new FileLocator($configDirectories);
        $studiosRessourceFile = $fileLocator->locate('../../ressources/Studios.json', false, false);

        $content = file_get_contents($studiosRessourceFile[0]);
        $studiosJson = json_decode($content, true);

        $maxPrice = 0;
        $mainPrice = $studiosJson[0]['price'];

        foreach ($studiosJson as $rkey => $studio) {

            if ($studio['price'] > $maxPrice) {
                $maxPrice = $studio['price'];
            }

            if ($studio['price'] < $mainPrice) {
                $mainPrice = $studio['price'];
            }
        }


        $builder
            ->add('city', TextType::class)
            ->add('price', RangeType::class, [
                'required' => false,
                'attr' => [
                    'min' => $mainPrice,
                    'max' => $maxPrice
                ]
            ])
            ->add('rangeValue', TextType::class, ['attr' => [ 'readonly' => true]])
            ->add('trainer', ChoiceType::class, [
                'choices' => [
                    'trainer' => [
                        'Ja' => 1,
                        'Nein' => 2,
                        'nur an Werktagen' => 3,
                        'nur an Wochenenden' => 4
                    ],
                ],
            ])
            ->add('24HoursOpen', CheckboxType::class, [
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
