import torch
import torch.nn as nn
from torchvision.models import efficientnet_b0, EfficientNet_B0_Weights

def build_model(num_classes=10, pretrained=True):
    """
    Builds EfficientNetB0 architecture with custom 10-class linear classifier.
    PRD Bab 8: Arsitektur Model.
    """
    weights = EfficientNet_B0_Weights.DEFAULT if pretrained else None
    model = efficientnet_b0(weights=weights)

    # In EfficientNetB0:
    # model.classifier is Sequential(Dropout(p=0.2, inplace=True), Linear(in_features=1280, out_features=1000))
    in_features = model.classifier[1].in_features
    dropout_rate = model.classifier[0].p

    model.classifier = nn.Sequential(
        nn.Dropout(p=dropout_rate, inplace=True),
        nn.Linear(in_features, num_classes)
    )

    return model

def freeze_backbone(model):
    """
    Freezes all feature extractor backbone layers for E01 Transfer Learning baseline.
    PRD Bab 9: Backbone frozen.
    """
    for param in model.features.parameters():
        param.requires_grad = False
    for param in model.classifier.parameters():
        param.requires_grad = True
    return model

def unfreeze_upper_layers(model, num_stages_to_unfreeze=2):
    """
    Unfreezes the upper stages of EfficientNetB0 backbone for E02 Fine-Tuning.
    PRD Bab 10: Selected upper layers unfrozen.
    EfficientNetB0 has features[0..8].
    Unfreezing last 2 stages (features[7] and features[8]) + classifier.
    """
    # First ensure classifier is trainable
    for param in model.classifier.parameters():
        param.requires_grad = True

    total_stages = len(model.features)  # 9 stages (0 to 8)
    cutoff = total_stages - num_stages_to_unfreeze

    for idx, stage in enumerate(model.features):
        if idx >= cutoff:
            for param in stage.parameters():
                param.requires_grad = True
        else:
            for param in stage.parameters():
                param.requires_grad = False

    trainable_params = sum(p.numel() for p in model.parameters() if p.requires_grad)
    total_params = sum(p.numel() for p in model.parameters())
    print(f"Model modified: {trainable_params:,} trainable params out of {total_params:,} total.")
    return model

if __name__ == "__main__":
    m = build_model(10, pretrained=False)
    print("Initial parameters:", sum(p.numel() for p in m.parameters() if p.requires_grad))
    m = freeze_backbone(m)
    print("After freezing backbone:", sum(p.numel() for p in m.parameters() if p.requires_grad))
    m = unfreeze_upper_layers(m, num_stages_to_unfreeze=2)
    print("After unfreezing upper layers:", sum(p.numel() for p in m.parameters() if p.requires_grad))
