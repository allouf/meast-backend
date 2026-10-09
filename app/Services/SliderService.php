<?php

namespace App\Services;

use App\Repositories\Interfaces\SliderRepositoryInterface;
use App\Utilities\FileManager;

class SliderService
{
    protected $sliderRepository;

    public function __construct(SliderRepositoryInterface $sliderRepository)
    {
        $this->sliderRepository = $sliderRepository;
    }

    public function all()
    {
        return $this->sliderRepository->all();
    }
    public function getByCategory($categoryId)
    {
        return $this->sliderRepository->getByCategory($categoryId);
    }
    public function findById($id)
    {
        return $this->sliderRepository->findById($id);
    }

    public function create(array $data)
    {
        if (isset($data['image'])) {
            $data['image'] = FileManager::upload('sliders', $data['image']);
        }
        return $this->sliderRepository->create($data);
    }

    public function update(array $data, $id)
    {
        if (isset($data['image'])) {
            $slider = $this->sliderRepository->findById($id);
            if($slider->image && FileManager::fileExists($slider->image))
            {
                FileManager::delete($slider->image);
            }
            $data['image'] = FileManager::upload('sliders', $data['image']);
        }
        return $this->sliderRepository->update($data, $id);
    }

    public function delete($id)
    {
        $slider = $this->sliderRepository->findById($id);
        if($slider->image && FileManager::fileExists($slider->image))
            {
                FileManager::delete($slider->image);
            }
        return $this->sliderRepository->delete($id);
    }
}
