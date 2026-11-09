<?php

namespace App\Form;

use App\Entity\Car;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Model Name',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Body Type',
                'choices' => [
                    'Hatchback' => 'Hatchback',
                    'Sedan' => 'Sedan',
                    'SUV' => 'SUV',
                    'MPV' => 'MPV',
                ],
            ])
            ->add('price', IntegerType::class, [
                'label' => 'Daily Rate (₱)',
            ])
            ->add('gear', ChoiceType::class, [
                'label' => 'Transmission',
                'choices' => [
                    'Automatic' => 'Auto',
                    'Manual' => 'Manual',
                ],
            ])
            ->add('seats', IntegerType::class, [
                'label' => 'Seats',
            ])
            ->add('doors', IntegerType::class, [
                'label' => 'Doors',
            ])
            ->add('bagLarge', IntegerType::class, [
                'label' => 'Large Bags',
            ])
            ->add('bagSmall', IntegerType::class, [
                'label' => 'Small Bags',
            ])
            ->add('kids', IntegerType::class, [
                'label' => 'Child Seats',
            ])
            ->add('img', TextType::class, [
                'label' => 'Image Path',
            ])
            ->add('aircon', CheckboxType::class, [
                'label' => 'Air Conditioning',
                'required' => false,
            ])
            ->add('available', CheckboxType::class, [
                'label' => 'Available for Booking',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Car::class,
        ]);
    }
}
