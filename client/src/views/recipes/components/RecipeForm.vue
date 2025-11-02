<template>
  <div class="recipe-form">
    <el-form
      ref="dataForm"
      :rules="rules"
      :model="recipeData"
      label-position="top"
      style="width: 100%;"
      size="small"
    >
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="Recipe Name" prop="name">
            <el-input
              v-model="recipeData.name"
              placeholder="Enter recipe name"
              :disabled="isViewOnly"
            />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="Cuisine Type" prop="cuisine_type_id">
            <el-select
              v-model="recipeData.cuisine_type_id"
              placeholder="Select cuisine type"
              style="width: 100%"
              :disabled="isViewOnly"
            >
              <el-option
                v-for="item in cuisineTypes"
                :key="item.id"
                :label="item.name"
                :value="item.id"
              />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="Description" prop="description">
        <el-input
          v-model="recipeData.description"
          type="textarea"
          :rows="3"
          placeholder="Enter recipe description"
          :disabled="isViewOnly"
        />
      </el-form-item>

      <el-form-item label="Ingredients" prop="ingredients">
        <div class="ingredients-section">
          <div
            v-for="(ingredient, index) in recipeData.ingredients"
            :key="index"
            class="ingredient-item"
          >
            <el-input
              v-model="recipeData.ingredients[index]"
              placeholder="Enter ingredient"
              style="width: calc(100% - 50px); margin-right: 10px;"
              :disabled="isViewOnly"
            />
            <el-button
              v-if="!isViewOnly"
              type="danger"
              icon="el-icon-delete"
              size="mini"
              @click="removeIngredient(index)"
            />
          </div>
          <el-button
            v-if="!isViewOnly"
            type="primary"
            icon="el-icon-plus"
            size="mini"
            @click="addIngredient"
          >
            Add Ingredient
          </el-button>
        </div>
      </el-form-item>

      <el-form-item label="Cooking Steps" prop="steps">
        <div class="steps-section">
          <div
            v-for="(step, index) in recipeData.steps"
            :key="index"
            class="step-item"
          >
            <span class="step-number">{{ index + 1 }}.</span>
            <el-input
              v-model="recipeData.steps[index]"
              type="textarea"
              :rows="2"
              placeholder="Enter cooking step"
              style="width: calc(100% - 80px); margin-right: 10px;"
              :disabled="isViewOnly"
            />
            <el-button
              v-if="!isViewOnly"
              type="danger"
              icon="el-icon-delete"
              size="mini"
              @click="removeStep(index)"
            />
          </div>
          <el-button
            v-if="!isViewOnly"
            type="primary"
            icon="el-icon-plus"
            size="mini"
            @click="addStep"
          >
            Add Step
          </el-button>
        </div>
      </el-form-item>

      <el-form-item label="Recipe Image">
        <el-upload
          class="image-uploader"
          action=""
          :show-file-list="false"
          :before-upload="beforeImageUpload"
          :disabled="isViewOnly"
        >
          <img v-if="imagePreview || currentImage" :src="imagePreview || currentImage" class="recipe-image">
          <i v-else class="el-icon-plus image-uploader-icon" />
        </el-upload>
        <div class="upload-tip">Only jpg/png files, and less than 2MB</div>
      </el-form-item>

      <div v-if="dialogStatus === 'update' && recipeData.user" class="recipe-info">
        <el-divider content-position="left">Recipe Information</el-divider>
        <el-row :gutter="20">
          <el-col :span="12">
            <div class="info-item">
              <strong>Owner:</strong> {{ recipeData.user.name }} ({{ recipeData.user.email }})
            </div>
          </el-col>
          <el-col :span="12">
            <div class="info-item">
              <strong>Created:</strong> {{ recipeData.created_at | parseTime('{y}-{m}-{d} {h}:{i}') }}
            </div>
          </el-col>
        </el-row>
        <el-row v-if="recipeData.updated_at !== recipeData.created_at" :gutter="20">
          <el-col :span="12">
            <div class="info-item">
              <strong>Last Updated:</strong> {{ recipeData.updated_at | parseTime('{y}-{m}-{d} {h}:{i}') }}
            </div>
          </el-col>
        </el-row>
      </div>
    </el-form>

    <div slot="footer" class="dialog-footer">
      <el-button @click="$emit('cancel')">
        Cancel
      </el-button>
      <el-button
        v-if="!isViewOnly"
        type="primary"
        :loading="loading"
        @click="handleSubmit"
      >
        {{ dialogStatus === 'create' ? 'Create' : 'Update' }}
      </el-button>
    </div>
  </div>
</template>

<script>
import { createRecipe, updateRecipe } from '@/api/recipe'
import { parseTime } from '@/utils'

export default {
  name: 'RecipeForm',
  filters: {
    parseTime(time, cFormat) {
      return parseTime(time, cFormat)
    }
  },
  props: {
    recipeData: {
      type: Object,
      required: true
    },
    cuisineTypes: {
      type: Array,
      default: () => []
    },
    dialogStatus: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      loading: false,
      imageFile: null,
      imagePreview: null,
      rules: {
        name: [
          { required: true, message: 'Recipe name is required', trigger: 'blur' },
          { min: 3, max: 255, message: 'Length should be 3 to 255', trigger: 'blur' }
        ],
        description: [
          { required: true, message: 'Description is required', trigger: 'blur' }
        ],
        cuisine_type_id: [
          { required: true, message: 'Please select a cuisine type', trigger: 'change' }
        ],
        ingredients: [
          { required: true, message: 'At least one ingredient is required', trigger: 'blur' }
        ],
        steps: [
          { required: true, message: 'At least one step is required', trigger: 'blur' }
        ]
      }
    }
  },
  computed: {
    isViewOnly() {
      return this.dialogStatus === 'view'
    },
    currentImage() {
      // Check for new attachment system first
      if (this.recipeData.attachments && this.recipeData.attachments.length > 0) {
        const imageAttachment = this.recipeData.attachments.find(att => att.is_image)
        if (imageAttachment) {
          return imageAttachment.url
        }
      }
      // Fallback to old image field for backward compatibility
      return this.recipeData.image || null
    }
  },
  watch: {
    recipeData: {
      handler(newVal) {
        // Ensure ingredients and steps are arrays
        if (!Array.isArray(newVal.ingredients)) {
          this.$set(newVal, 'ingredients', [])
        }
        if (!Array.isArray(newVal.steps)) {
          this.$set(newVal, 'steps', [])
        }
        // Add at least one empty ingredient and step for new recipes
        if (this.dialogStatus === 'create') {
          if (newVal.ingredients.length === 0) {
            newVal.ingredients.push('')
          }
          if (newVal.steps.length === 0) {
            newVal.steps.push('')
          }
        }
      },
      immediate: true,
      deep: true
    }
  },
  methods: {
    addIngredient() {
      this.recipeData.ingredients.push('')
    },
    removeIngredient(index) {
      if (this.recipeData.ingredients.length > 1) {
        this.recipeData.ingredients.splice(index, 1)
      }
    },
    addStep() {
      this.recipeData.steps.push('')
    },
    removeStep(index) {
      if (this.recipeData.steps.length > 1) {
        this.recipeData.steps.splice(index, 1)
      }
    },
    beforeImageUpload(file) {
      const isJPGorPNG = file.type === 'image/jpeg' || file.type === 'image/png'
      const isLt2M = file.size / 1024 / 1024 < 2

      if (!isJPGorPNG) {
        this.$message.error('Recipe image must be JPG or PNG format!')
        return false
      }
      if (!isLt2M) {
        this.$message.error('Recipe image size cannot exceed 2MB!')
        return false
      }

      // Store the file object for upload
      this.imageFile = file
      
      // Show preview immediately
      const reader = new FileReader()
      reader.onload = (e) => {
        this.imagePreview = e.target.result
      }
      reader.readAsDataURL(file)

      return false // Prevent automatic upload
    },
    validateForm() {
      // Filter out empty ingredients and steps
      this.recipeData.ingredients = this.recipeData.ingredients.filter(item => item.trim() !== '')
      this.recipeData.steps = this.recipeData.steps.filter(item => item.trim() !== '')

      // Check if we have at least one ingredient and step
      if (this.recipeData.ingredients.length === 0) {
        this.$message.error('At least one ingredient is required')
        return false
      }
      if (this.recipeData.steps.length === 0) {
        this.$message.error('At least one cooking step is required')
        return false
      }
      return true
    },
    handleSubmit() {
      this.$refs.dataForm.validate((valid) => {
        if (valid && this.validateForm()) {
          this.loading = true
          
          // Create FormData for file upload
          const formData = new FormData()
          formData.append('name', this.recipeData.name)
          formData.append('description', this.recipeData.description)
          formData.append('cuisine_type_id', this.recipeData.cuisine_type_id)
          
          // Add ingredients and steps as arrays
          this.recipeData.ingredients.forEach((ingredient, index) => {
            formData.append(`ingredients[${index}]`, ingredient)
          })
          this.recipeData.steps.forEach((step, index) => {
            formData.append(`steps[${index}]`, step)
          })
          
          // Add image file if exists
          if (this.imageFile) {
            formData.append('image', this.imageFile)
          }

          if (this.dialogStatus === 'create') {
            createRecipe(formData).then(() => {
              this.loading = false
              this.$message({
                type: 'success',
                message: 'Recipe created successfully!'
              })
              this.$emit('submit')
            }).catch(() => {
              this.loading = false
            })
          } else {
            // For updates, add _method field for Laravel
            formData.append('_method', 'PUT')
            updateRecipe(this.recipeData.id, formData).then(() => {
              this.loading = false
              this.$message({
                type: 'success',
                message: 'Recipe updated successfully!'
              })
              this.$emit('submit')
            }).catch(() => {
              this.loading = false
            })
          }
        }
      })
    },
    clearValidate() {
      this.$refs.dataForm.clearValidate()
    },
    clearImage() {
      this.imageFile = null
      this.imagePreview = null
      this.recipeData.image = null
      // Clear attachments if they exist
      if (this.recipeData.attachments) {
        this.recipeData.attachments = this.recipeData.attachments.filter(att => !att.is_image)
      }
    }
  }
}
</script>

<style scoped>
.recipe-form {
  padding: 20px;
}

.ingredients-section,
.steps-section {
  border: 1px solid #E4E7ED;
  border-radius: 8px;
  padding: 20px;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  margin-bottom: 10px;
}

.ingredient-item,
.step-item {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.step-number {
  min-width: 30px;
  font-weight: bold;
  color: #409EFF;
}

.image-uploader {
  border: 2px dashed #d9d9d9;
  border-radius: 8px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  width: 200px;
  height: 200px;
  transition: border-color 0.3s;
  background-color: #fafbfc;
}

.image-uploader:hover {
  border-color: #409EFF;
  background-color: #f0f9ff;
}

.image-uploader-icon {
  font-size: 32px;
  color: #8c939d;
  width: 200px;
  height: 200px;
  line-height: 200px;
  text-align: center;
}

.recipe-image {
  width: 200px;
  height: 200px;
  display: block;
  object-fit: cover;
  border-radius: 6px;
}

.upload-tip {
  margin-top: 5px;
  font-size: 12px;
  color: #909399;
}

.recipe-info {
  margin-top: 20px;
  padding: 15px;
  background-color: #F5F7FA;
  border-radius: 4px;
}

.info-item {
  margin-bottom: 10px;
  color: #606266;
}

.dialog-footer {
  text-align: right;
  padding: 15px 20px;
  border-top: 1px solid #e4e7ed;
}

/* Enhanced form styling */
.recipe-form .el-form-item {
  margin-bottom: 20px;
}

.recipe-form .el-form-item__label {
  font-size: 14px;
  font-weight: 600;
  color: #2c3e50;
}

.recipe-form .el-input__inner {
  height: 40px;
  line-height: 40px;
  font-size: 14px;
  border-radius: 6px;
  border: 1px solid #dcdfe6;
  transition: border-color 0.2s cubic-bezier(0.645, 0.045, 0.355, 1);
}

.recipe-form .el-input__inner:focus {
  border-color: #409eff;
  box-shadow: 0 0 0 2px rgba(64, 158, 255, 0.1);
}

.recipe-form .el-textarea__inner {
  font-size: 14px;
  padding: 12px 15px;
  border-radius: 6px;
  border: 1px solid #dcdfe6;
  transition: border-color 0.2s cubic-bezier(0.645, 0.045, 0.355, 1);
}

.recipe-form .el-textarea__inner:focus {
  border-color: #409eff;
  box-shadow: 0 0 0 2px rgba(64, 158, 255, 0.1);
}

.recipe-form .el-select .el-input__inner {
  height: 40px;
  line-height: 40px;
}

.recipe-form .el-button--small {
  padding: 6px 12px;
  font-size: 12px;
}

.recipe-form .ingredient-item, 
.recipe-form .step-item {
  margin-bottom: 8px;
}

.recipe-form .ingredient-item .el-input,
.recipe-form .step-item .el-input {
  margin-right: 8px;
}

.recipe-form .ingredient-item .el-button,
.recipe-form .step-item .el-button {
  padding: 4px 8px;
  font-size: 11px;
}
</style>
