<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\FavoriteRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=FavoriteRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @Vich\Uploadable
 */
class Favorite
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @ORM\OneToMany(targetEntity=Message::class, mappedBy="favorite")
     * @Assert\NotBlank(message="Ce champ ne peut pas être vide")
     */
    private $messages;

    
    /**
     * @ORM\ManyToOne(targetEntity=Sender::class, inversedBy="favorites")
     * @ORM\JoinColumn(nullable=false)
     */
    private $sender;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="favorites")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;    

    /**
     * Undocumented variable
     *
     * @var Person
     */
    private $phone;

    /**
     * @ORM\ManyToMany(targetEntity=Group::class, inversedBy="favorites")
     */
    private $groupes;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $segment;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $media;

    /**
     * @Vich\UploadableField(mapping="whatsapp_media", fileNameProperty="media")
     * @var File
     */
    private $imageFile;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $whatsapp;

    /**
     * @ORM\OneToMany(targetEntity=Media::class, mappedBy="favorite")
     */
    private $medias;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->groupes = new ArrayCollection();
        $this->medias = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     *
     * @return void
     */
    public function addSegment() {
        
        $this->segment =(int) (1+ strlen($this->content)/50);   
    }

    public function countMessages(){
        return count($this->messages);
    }

    public function errorMessages(){
        $messages = [];
        foreach($this->messages as $k => $message){
            if(is_null($message->getState()) ) $messages []= $message;
        }
        return count($messages);
    }

    public function successMessages(){
        $messages = [];
        foreach($this->messages as $k => $message){
            if($message->getState() == 1) $messages []= $message;
        }
        return count($messages);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return Collection|Message[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setFavorite($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->contains($message)) {
            $this->messages->removeElement($message);
            // set the owning side to null (unless already changed)
            if ($message->getFavorite() === $this) {
                $message->setFavorite(null);
            }
        }

        return $this;
    }

    public function getSender(): ?Sender
    {
        return $this->sender;
    }

    public function setSender(?Sender $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPhone(): ?Person
    {
        return $this->phone;
    }

    public function setPhone(?Person $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return Collection|Group[]
     */
    public function getGroupes(): Collection
    {
        return $this->groupes;
    }

    public function addGroupe(Group $groupe): self
    {
        if (!$this->groupes->contains($groupe)) {
            $this->groupes[] = $groupe;
        }

        return $this;
    }

    public function removeGroupe(Group $groupe): self
    {
        if ($this->groupes->contains($groupe)) {
            $this->groupes->removeElement($groupe);
        }

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getSegment(): ?int
    {
        return $this->segment;
    }

    public function setSegment(int $segment): self
    {
        $this->segment = $segment;

        return $this;
    }

    public function getMedia(): ?string
    {
        return $this->media;
    }

    public function setMedia(?string $media): self
    {
        $this->media = $media;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getWhatsapp(): ?bool
    {
        return $this->whatsapp;
    }

    public function setWhatsapp(?bool $whatsapp): self
    {
        $this->whatsapp = $whatsapp;

        return $this;
    }

    public function setImageFile(File $image = null)
    {
        $this->imageFile = $image;

        // VERY IMPORTANT:
        // It is required that at least one field changes if you are using Doctrine,
        // otherwise the event listeners won't be called and the file is lost
        if ($image) {
            // if 'updatedAt' is not defined in your entity, use another property
            $this->updatedAt = new \DateTime('now');
        }
    }

    public function getImageFile()
    {
        return $this->imageFile;
    }

    /**
     * @return Collection|Media[]
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(Media $media): self
    {
        if (!$this->medias->contains($media)) {
            $this->medias[] = $media;
            $media->setFavorite($this);
        }

        return $this;
    }

    public function removeMedia(Media $media): self
    {
        if ($this->medias->removeElement($media)) {
            // set the owning side to null (unless already changed)
            if ($media->getFavorite() === $this) {
                $media->setFavorite(null);
            }
        }

        return $this;
    }
}
