from pathlib import Path
from PIL import Image
import torch
from torch.utils.data import Dataset
import torchvision.transforms as T

# Standard ImageNet normalization matching EfficientNetB0 pretrained weights
IMAGENET_MEAN = [0.485, 0.456, 0.406]
IMAGENET_STD = [0.229, 0.224, 0.225]
TARGET_SIZE = (224, 224)

def get_train_transforms():
    """Augmentation pipeline applied ONLY to the training dataset."""
    return T.Compose([
        T.RandomResizedCrop(TARGET_SIZE, scale=(0.85, 1.0)),
        T.RandomHorizontalFlip(p=0.5),
        T.RandomRotation(degrees=15),
        T.ColorJitter(brightness=0.1, contrast=0.1, saturation=0.05),
        T.ToTensor(),
        T.Normalize(mean=IMAGENET_MEAN, std=IMAGENET_STD),
    ])

def get_eval_transforms():
    """Deterministic preprocessing for validation and test datasets."""
    return T.Compose([
        T.Resize(TARGET_SIZE),
        T.ToTensor(),
        T.Normalize(mean=IMAGENET_MEAN, std=IMAGENET_STD),
    ])

def get_inference_transforms():
    """Deterministic preprocessing for production inference (identical to eval)."""
    return get_eval_transforms()

class TomatoDataset(Dataset):
    """PyTorch Dataset reading from split manifest."""
    def __init__(self, df, transform=None):
        self.df = df.reset_index(drop=True)
        self.transform = transform

    def __len__(self):
        return len(self.df)

    def __getitem__(self, idx):
        row = self.df.iloc[idx]
        img_path = row["image_path"]
        label = int(row["label"])

        with Image.open(img_path) as img:
            img = img.convert("RGB")
            if self.transform:
                tensor_img = self.transform(img)
            else:
                tensor_img = T.ToTensor()(img)

        return tensor_img, label

if __name__ == "__main__":
    print("Testing transforms consistency...")
    train_tf = get_train_transforms()
    eval_tf = get_eval_transforms()
    inf_tf = get_inference_transforms()

    dummy_img = Image.new("RGB", (256, 256), color=(128, 128, 128))
    t_train = train_tf(dummy_img)
    t_eval = eval_tf(dummy_img)
    t_inf = inf_tf(dummy_img)

    assert t_train.shape == (3, 224, 224), f"Train shape mismatch: {t_train.shape}"
    assert t_eval.shape == (3, 224, 224), f"Eval shape mismatch: {t_eval.shape}"
    assert t_inf.shape == (3, 224, 224), f"Inference shape mismatch: {t_inf.shape}"
    assert torch.allclose(t_eval, t_inf), "Eval and Inference transforms are not identical!"

    print("Transforms verified successfully: shapes (3, 224, 224) and eval/inf consistency PASS!")
