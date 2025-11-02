import axios from 'axios'
import { MessageBox, Message } from 'element-ui'
import store from '@/store'
import { getToken } from '@/utils/auth'

// create an axios instance
const service = axios.create({
  baseURL: process.env.VUE_APP_BASE_API, // url = base url + request url
  // withCredentials: true, // send cookies when cross-domain requests
  timeout: 5000 // request timeout
})

// request interceptor
service.interceptors.request.use(
  config => {
    // do something before request is sent

    if (store.getters.token) {
      // let each request carry token
      // Laravel Sanctum uses Bearer token
      config.headers['Authorization'] = `Bearer ${getToken()}`
    }
    return config
  },
  error => {
    // do something with request error
    console.log(error) // for debug
    return Promise.reject(error)
  }
)

// response interceptor
service.interceptors.response.use(
  /**
   * Determine the request status by custom code
   * Handle successful responses
   */
  response => {
    const res = response.data

    // Laravel API returns success: true/false
    if (res.success === false) {
      // Show error message for failed operations
      Message({
        message: res.message || 'Operation failed',
        type: 'error',
        duration: 5 * 1000
      })

      return Promise.reject(new Error(res.message || 'Operation failed'))
    } else {
      return res
    }
  },
  error => {
    console.log('API Error:', error) // for debug
    
    let message = 'Network Error'
    
    if (error.response) {
      // Server responded with error status
      const { status, data } = error.response
      
      if (data && data.message) {
        message = data.message
      } else if (data && data.errors) {
        // Laravel validation errors
        const errorMessages = Object.values(data.errors).flat()
        message = errorMessages.join(', ')
      } else {
        // Default messages based on status code
        switch (status) {
          case 400:
            message = 'Bad Request'
            break
          case 401:
            message = 'Invalid login credentials'
            // Handle 401 Unauthorized (invalid token)
            MessageBox.confirm('You have been logged out, you can cancel to stay on this page, or log in again', 'Confirm logout', {
              confirmButtonText: 'Re-Login',
              cancelButtonText: 'Cancel',
              type: 'warning'
            }).then(() => {
              store.dispatch('user/resetToken').then(() => {
                location.reload()
              })
            })
            break
          case 403:
            message = 'Access Denied'
            break
          case 404:
            message = 'Resource Not Found'
            break
          case 422:
            message = 'Validation Error'
            break
          case 500:
            message = 'Internal Server Error'
            break
          default:
            message = `Error ${status}: ${error.response.statusText}`
        }
      }
    } else if (error.request) {
      // Request was made but no response received
      message = 'No response from server'
    } else {
      // Request setup error
      message = error.message || 'Request failed'
    }

    // Show error message
    Message({
      message: message,
      type: 'error',
      duration: 5 * 1000
    })
    
    return Promise.reject(error)
  }
)

export default service
